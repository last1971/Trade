<?php

namespace App\Services\Marking;

use App\ChzBatch;
use App\Notifications\ChzOutboxProblemNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Очередь отправки документов в Честный знак. Единственное место, которое знает,
 * как пачка превращается в запрос к chz-сервису и что делать с ответом.
 *
 * Что в пачку положить, решает не этот сервис: деление и нанесение собирает
 * Delphi в момент нажатия кнопки, вывод из оборота (когда дойдут руки) — ozon.
 * Здесь только отправка, проверка и отметки в MARKCODES.
 *
 * Ходит в сервис через ChzClient — второго клиента к нему в проекте быть не должно.
 */
class ChzOutboxService
{
    /**
     * Жизненный цикл кода в ГИС МТ: EMITTED -> APPLIED (отчёт о нанесении)
     * -> INTRODUCED (в обороте) -> продажа/УПД (RETIRED). Успехом считаем нужный
     * статус И ВСЁ, ЧТО ПОСЛЕ: код мог уехать дальше, пока мы спрашивали.
     * Для деления INTRODUCED проверен живьём (78 кодов, 08.09.2026);
     * для нанесения APPLIED — из руководства, подтвердить первым прогоном.
     */
    private const AFTER_APPLY = ['APPLIED', 'INTRODUCED', 'RETIRED'];
    private const AFTER_DIVISION = ['INTRODUCED', 'RETIRED'];
    private const AFTER_INTRO = ['INTRODUCED', 'RETIRED'];

    /** Документ True API отбит: дальше ждать нечего. */
    private const DOC_FAILED = ['REJECTED', 'CHECKED_NOT_OK', 'PROCESSING_ERROR'];

    /** Сколько ждём ГИС МТ, прежде чем звать человека: лаг в полтора часа — норма. */
    private const STUCK_HOURS = 6;

    /** Firebird не любит длинные списки параметров — режем IN на куски. */
    private const CHUNK = 50;

    /**
     * Метка «чем кончилось — знает только ЧЗ»: ответа на отправку не было вовсе.
     * Такую пачку кнопка «Повторить» не трогает: второй документ на те же коды
     * хуже, чем разбор руками.
     */
    private const UNKNOWN = 'НЕИЗВЕСТНО: ';

    public function __construct(private ChzClient $client)
    {
    }

    /** Выключатель инсталляции: на базе без кодов воркер молчит. */
    public function enabled(): bool
    {
        return (bool)config('marking.chz.outbox');
    }

    /** Один проход воркера. Возвращает строки для вывода команды. */
    public function tick(): array
    {
        return array_merge($this->promoteWaiting(), $this->sendNext(), $this->checkSent());
    }

    /** Ввод в оборот ждёт, пока его нанесение подтвердится. */
    private function promoteWaiting(): array
    {
        $lines = [];
        $waiting = ChzBatch::where('STATUS', ChzBatch::STATUS_WAIT)->orderBy('ID')->get();
        foreach ($waiting as $batch) {
            $parent = $batch->PARENT_ID ? ChzBatch::find($batch->PARENT_ID) : null;
            if (!$parent) {
                $this->fail($batch, 'Пачка-предшественник не найдена: PARENT_ID = ' . $batch->PARENT_ID);
                $lines[] = "пачка №{$batch->ID}: предшественник потерян";
                continue;
            }
            if ($parent->STATUS === ChzBatch::STATUS_ERROR) {
                $this->fail($batch, "Предшественник №{$parent->ID} отбит: " . $parent->ERROR_TEXT);
                $lines[] = "пачка №{$batch->ID}: предшественник отбит";
                continue;
            }
            if ($parent->STATUS === ChzBatch::STATUS_DONE) {
                $batch->STATUS = ChzBatch::STATUS_READY;
                $batch->save();
                $lines[] = "пачка №{$batch->ID}: предшественник подтверждён, готова к отправке";
            }
        }
        return $lines;
    }

    /**
     * Отправляем по одной пачке за проход: спешить некуда, а параллельные
     * документы в ЧЗ нам не нужны.
     */
    private function sendNext(): array
    {
        $batch = ChzBatch::where('STATUS', ChzBatch::STATUS_READY)
            ->orderBy('ID')
            ->first();
        if (!$batch) {
            return [];
        }

        try {
            $items = $this->items($batch);
            if (!$items) {
                $this->fail($batch, 'В пачке нет кодов, пригодных к отправке (нет полного КМ или родителя)');
                return ["пачка №{$batch->ID}: отправлять нечего"];
            }
            $answer = $this->client->post($this->path($batch), [
                'inn' => $this->inn(),
                'items' => $items,
            ], ['X-Request-Id' => $this->requestId($batch)]);
        } catch (MarkingException $e) {
            // «уже отправлен, ответ не получен» — сервис сам отказывается повторять,
            // и мы тоже: что там на самом деле, знает только ЧЗ.
            $unknown = mb_strpos($e->getMessage(), 'ответ не получен') !== false;
            $this->fail($batch, ($unknown ? self::UNKNOWN : '') . $e->getMessage());
            return ["пачка №{$batch->ID}: отказ — " . $e->getMessage()];
        }

        // Нанесение и деление возвращают отчёт СУЗ, ввод в оборот — документ True API.
        // Это разные сущности с разными номерами, поэтому и колонки разные.
        $intro = $this->isIntro($batch);
        $sent = $intro ? ($answer['documents'] ?? []) : ($answer['reports'] ?? []);
        $key = $intro ? 'docId' : 'reportId';
        $id = $sent[0][$key] ?? null;
        if ($intro) {
            $batch->DOC_UUID = $id;
        } else {
            $batch->REPORT_ID = $id;
        }
        $batch->STATUS = ChzBatch::STATUS_SENT;
        $batch->SENT_AT = now();
        // Пачка обязана быть одним документом. Сервис режет по 500 кодов, поэтому
        // несколько ответов означает, что пачку собрали слишком большой.
        if (count($sent) > 1) {
            $ids = implode(', ', array_column($sent, $key));
            $batch->ERROR_TEXT = $this->cut('Пачка ушла ' . count($sent)
                . ' документами, проверяется только первый: ' . $ids);
            $this->notify($batch, $batch->ERROR_TEXT);
        }
        $batch->save();

        if (!$id) {
            $this->fail($batch, self::UNKNOWN . 'сервис не вернул номер документа — проверьте в Честном знаке, что там на самом деле');
            return ["пачка №{$batch->ID}: ответ без номера"];
        }
        return ["пачка №{$batch->ID} ({$batch->KIND}, кодов " . count($items) . "): документ {$id}"];
    }

    /** Проверяем отправленные по статусам самих кодов: статус отчёта в СУЗ навсегда SENT. */
    private function checkSent(): array
    {
        $lines = [];
        foreach (ChzBatch::where('STATUS', ChzBatch::STATUS_SENT)->orderBy('ID')->get() as $batch) {
            try {
                $answer = $this->client->get($this->checkPath($batch), ['deep' => 1]);
            } catch (MarkingException $e) {
                // Сервис недоступен — пачку не трогаем, спросим на следующей минуте.
                $lines[] = "пачка №{$batch->ID}: проверка не удалась — " . $e->getMessage();
                continue;
            }

            $reason = $answer['errorReason'] ?? null;
            if (in_array($answer['status'] ?? null, self::DOC_FAILED, true) || $reason) {
                $this->fail($batch, 'Честный знак отбил отчёт: ' . ($reason ?: 'без причины'));
                $lines[] = "пачка №{$batch->ID}: отказ ЧЗ";
                continue;
            }

            $pending = self::pending($batch->kis(), $answer['cises'] ?? [], trim((string)$batch->KIND));

            if (!$pending) {
                $note = $this->confirm($batch);
                $lines[] = "пачка №{$batch->ID}: подтверждена" . ($note ? " ({$note})" : '');
                continue;
            }

            // ГИС МТ отстаёт — это норма, ошибкой не считаем, но не вечно.
            if ($batch->SENT_AT && now()->diffInHours($batch->SENT_AT) >= self::STUCK_HOURS) {
                $this->notify($batch, 'Пачка висит без подтверждения больше ' . self::STUCK_HOURS
                    . ' часов. Коды без нужного статуса: ' . implode('; ', array_slice($pending, 0, 10)));
            }
            $lines[] = "пачка №{$batch->ID}: ждём ГИС МТ, без подтверждения " . count($pending);
        }
        return $lines;
    }

    /**
     * Пачка подтверждена: ставим отметки, которых до нас не ставил никто,
     * и сверяем количество на кодах с тем, что у ЧЗ.
     */
    private function confirm(ChzBatch $batch): ?string
    {
        $kis = $batch->kis();
        $this->stamp($kis, 'REPORTED_AT');
        // Деление вводит ребёнка в оборот тем же документом — у нас это поле пустое.
        // У ввода в оборот отметку обычно уже поставила MARKCODES_INTRODUCE_BY_SCODE,
        // но пачка могла прийти и из другого места: stamp заполняет только пустые.
        if ($batch->KIND === ChzBatch::KIND_DIVISION || $this->isIntro($batch)) {
            $this->stamp($kis, 'ENTERED_CIRCULATION_AT');
        }

        $note = $batch->KIND === ChzBatch::KIND_DIVISION ? $this->checkQuantities($batch) : null;

        $batch->STATUS = ChzBatch::STATUS_DONE;
        $batch->CONFIRMED_AT = now();
        $batch->ERROR_TEXT = $note ? $this->cut($note) : null;
        $batch->save();

        if ($note) {
            $this->notify($batch, $note);
        }
        return $note;
    }

    /**
     * После деления остаток родителя ведёт ЧЗ, а количество у ребёнка — мы.
     * Разошлись — документ всё равно принят, но человек должен это увидеть.
     */
    private function checkQuantities(ChzBatch $batch): ?string
    {
        $rows = $this->rows(
            'select ch.KI, COALESCE(ch.QUANTITY, 1) as QTY, p.KI as PARENT_KI, COALESCE(p.QUANTITY, 1) as PARENT_QTY '
            . 'from CHZ_BATCH_KI k '
            . 'join MARKCODES ch on ch.KI = k.KI '
            . 'join MARKCODES_REMARK r on r.NEW_MARKCODE = ch.MARKCODE and r.REMARK_TYPE = 2 '
            . 'join MARKCODES p on p.MARKCODE = r.OLD_MARKCODE '
            . 'where k.BATCH_ID = ?',
            [$batch->ID]
        );
        if (!$rows) {
            return null;
        }

        $ours = [];
        foreach ($rows as $row) {
            $ours[trim((string)$row->KI)] = intval($row->QTY);
            $ours[trim((string)$row->PARENT_KI)] = intval($row->PARENT_QTY);
        }

        try {
            $answer = $this->client->post('codes/info', ['inn' => $this->inn(), 'codes' => array_keys($ours)]);
        } catch (MarkingException $e) {
            return 'Количество не сверено: ' . $e->getMessage();
        }

        $diff = [];
        foreach ($answer['codes'] ?? [] as $code) {
            $ki = $code['ki'] ?? null;
            if ($ki === null || !array_key_exists($ki, $ours) || !array_key_exists('quantity', $code)) {
                continue;
            }
            if (intval($code['quantity']) !== $ours[$ki]) {
                $diff[] = "{$ki}: у нас {$ours[$ki]}, в ЧЗ " . intval($code['quantity']);
            }
        }
        return $diff ? 'Расхождение количества после деления — ' . implode('; ', array_slice($diff, 0, 10)) : null;
    }

    /**
     * Повтор отбитой пачки. Номер пачки — он же идентификатор запроса в сервисе,
     * поэтому переиспользовать его нельзя: делаем новую пачку с тем же списком КИ.
     * Ждущий ввод в оборот перецепляем на неё, иначе он останется сиротой.
     */
    public function retry(ChzBatch $batch): array
    {
        if ($batch->STATUS !== ChzBatch::STATUS_ERROR) {
            throw new MarkingException('Повторить можно только отбитую пачку');
        }
        if (mb_strpos((string)$batch->ERROR_TEXT, self::UNKNOWN) === 0) {
            throw new MarkingException(
                'Эта пачка ушла в Честный знак, но ответа не было. Повтор запрещён: '
                . 'сперва посмотрите в Честном знаке, что с кодами на самом деле'
            );
        }
        $kis = $batch->kis();
        if (!$kis) {
            throw new MarkingException('В пачке нет кодов');
        }

        $new = new ChzBatch();
        $new->KIND = $batch->KIND;
        $new->CNT = count($kis);
        $new->SCODE = $batch->SCODE;
        $new->STATUS = ChzBatch::STATUS_READY;
        $new->CREATED_BY = mb_substr('web:' . (optional(auth()->user())->name ?? '?'), 0, 32);

        // Транзакция по правилам этого драйвера: DB::transaction() на Firebird
        // падает с «There is already an active transaction» — соединение и так
        // держит открытую транзакцию (см. GoodClassifyService).
        $connection = DB::connection('firebird');
        $connection->getPdo()->setAttribute(\PDO::ATTR_AUTOCOMMIT, 0);
        $connection->beginTransaction();
        try {
            $new->save();
            foreach ($kis as $ki) {
                // По строке за раз: несколько VALUES одним INSERT Firebird не умеет.
                $connection->table('CHZ_BATCH_KI')->insert(['BATCH_ID' => $new->ID, 'KI' => $ki]);
            }
            ChzBatch::where('PARENT_ID', $batch->ID)
                ->whereIn('STATUS', [ChzBatch::STATUS_WAIT, ChzBatch::STATUS_ERROR])
                ->update(['PARENT_ID' => $new->ID, 'STATUS' => ChzBatch::STATUS_WAIT, 'ERROR_TEXT' => null]);
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        } finally {
            $connection->getPdo()->setAttribute(\PDO::ATTR_AUTOCOMMIT, 1);
        }

        return ['id' => $new->ID, 'cnt' => $new->CNT];
    }

    /** Тело запроса по виду пачки. Коды не хранятся в очереди — собираем из базы. */
    private function items(ChzBatch $batch): array
    {
        if ($batch->KIND === ChzBatch::KIND_DIVISION) {
            $rows = $this->rows(
                'select ch.KM_FULL, r.TRANSFERRED_QTY as QTY, p.KM_FULL as PARENT_KM '
                . 'from CHZ_BATCH_KI k '
                . 'join MARKCODES ch on ch.KI = k.KI '
                . 'join MARKCODES_REMARK r on r.NEW_MARKCODE = ch.MARKCODE and r.REMARK_TYPE = 2 '
                . 'join MARKCODES p on p.MARKCODE = r.OLD_MARKCODE '
                . 'where k.BATCH_ID = ? and ch.KM_FULL is not null and p.KM_FULL is not null '
                . 'order by ch.MARKCODE',
                [$batch->ID]
            );
            return array_map(fn($row) => [
                'km' => (string)$row->KM_FULL,
                'quantity' => intval($row->QTY),
                'parentKm' => (string)$row->PARENT_KM,
            ], $rows);
        }

        $rows = $this->rows(
            'select m.KM_FULL, COALESCE(m.QUANTITY, 1) as QTY from CHZ_BATCH_KI k '
            . 'join MARKCODES m on m.KI = k.KI '
            . 'where k.BATCH_ID = ? and m.KM_FULL is not null order by m.MARKCODE',
            [$batch->ID]
        );
        return array_map(fn($row) => [
            'km' => (string)$row->KM_FULL,
            'quantity' => intval($row->QTY),
        ], $rows);
    }

    private function path(ChzBatch $batch): string
    {
        if ($this->isIntro($batch)) {
            return 'introduce';
        }
        return $batch->KIND === ChzBatch::KIND_DIVISION ? 'apply/division' : 'apply';
    }

    /** Проверка: у ввода в оборот документ True API, у остальных отчёт СУЗ. */
    private function checkPath(ChzBatch $batch): string
    {
        return $this->isIntro($batch)
            ? 'document/' . $batch->DOC_UUID
            : 'apply/' . $batch->REPORT_ID;
    }

    private function isIntro(ChzBatch $batch): bool
    {
        return trim((string)$batch->KIND) === ChzBatch::KIND_INTRO;
    }

    /**
     * Повтор с тем же идентификатором сервис не отправит вторым документом.
     * Префикс обязателен: номера пачек в базах опта и магазина совпадают,
     * а база сервиса одна на обе.
     */
    private function requestId(ChzBatch $batch): string
    {
        return trim((string)config('marking.chz.prefix')) . '-batch-' . $batch->ID;
    }

    private function inn(): string
    {
        $inn = trim((string)config('marking.chz.inn'));
        if ($inn === '') {
            throw new MarkingException('Не задан ИНН организации (MARKING_CHZ_INN)');
        }
        return $inn;
    }

    /**
     * Коды пачки, которые Честный знак ещё не подтвердил, — «КИ = статус».
     * Пусто — пачку можно закрывать. Чистая функция: тут вся суть проверки,
     * поэтому она и вынесена отдельно (см. ChzOutboxServiceTest).
     */
    public static function pending(array $kis, array $cises, string $kind): array
    {
        if ($kind === ChzBatch::KIND_DIVISION) {
            $accepted = self::AFTER_DIVISION;
        } elseif ($kind === ChzBatch::KIND_INTRO) {
            $accepted = self::AFTER_INTRO;
        } else {
            $accepted = self::AFTER_APPLY;
        }
        $statuses = self::cisStatuses($cises);
        $pending = [];
        foreach ($kis as $ki) {
            if (!in_array($statuses[$ki] ?? '', $accepted, true)) {
                $pending[] = $ki . ' = ' . ($statuses[$ki] ?? 'нет ответа');
            }
        }
        return $pending;
    }

    /** Ответ ГИС МТ приходит как есть: КИ лежит в requestedCis либо в cis. */
    private static function cisStatuses(array $cises): array
    {
        $statuses = [];
        foreach ($cises as $entry) {
            $info = $entry['cisInfo'] ?? $entry;
            $ki = $info['requestedCis'] ?? $info['cis'] ?? null;
            if ($ki !== null) {
                $statuses[$ki] = $info['status'] ?? null;
            }
        }
        return $statuses;
    }

    /** Отметка ставится один раз: повторный проход ничего не перетирает. */
    private function stamp(array $kis, string $column): void
    {
        foreach (array_chunk($kis, self::CHUNK) as $part) {
            $in = implode(',', array_fill(0, count($part), '?'));
            DB::connection('firebird')->update(
                "update MARKCODES set {$column} = CURRENT_TIMESTAMP where KI in ({$in}) and {$column} is null",
                $part
            );
        }
    }

    private function fail(ChzBatch $batch, string $reason): void
    {
        $batch->STATUS = ChzBatch::STATUS_ERROR;
        $batch->ERROR_TEXT = $this->cut($reason);
        $batch->save();
        $this->notify($batch, $reason);
    }

    /** Не чаще раза в сутки на пачку: воркер ходит каждую минуту. */
    private function notify(?ChzBatch $batch, string $reason, string $key = null): void
    {
        $address = config('mail.chz_notify');
        if (!$address) {
            return;
        }
        $key = 'chz-outbox-' . ($key ?? ('batch-' . $batch->ID));
        if (!Cache::add($key, true, now()->addDay())) {
            return;
        }
        try {
            Notification::route('mail', $address)->notify(new ChzOutboxProblemNotification($batch, $reason));
        } catch (Throwable $e) {
            $this->log('error', 'chz:outbox письмо не отправлено: ' . $e->getMessage());
        }
    }

    private function rows(string $sql, array $bindings): array
    {
        return DB::connection('firebird')->select($sql, $bindings);
    }

    private function cut(string $text): string
    {
        return mb_substr($text, 0, 500);
    }

    private function log(string $level, string $message): void
    {
        try {
            Log::$level($message);
        } catch (Throwable $e) {
            // логгер недоступен — воркеру это не мешает
        }
    }
}
