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
    /** Дальше RETIRED кода не двигают: для вывода это единственный успех. */
    private const AFTER_RETIRE = ['RETIRED'];

    /** Все виды вывода из оборота: документ True API, путь retire, успех — RETIRED. */
    private const RETIRE_KINDS = [ChzBatch::KIND_RETIRE, ChzBatch::KIND_RETIRE_UPD, ChzBatch::KIND_RETIRE_ACT];

    /**
     * Коды, ждущие вывода из оборота. Те же предикаты, что у вкладки «ЧЗ»
     * в админке ozon (Trade2006ChzService), плюс CHZ_SKIP_AT: снятый с отправки
     * код не собирается в пачку никогда.
     *   вывод по продаже маркетплейса — TRANSFER_TYPE=3, документ = счёт (S);
     *   вывод по УПД покупателю вне ЧЗ — TRANSFER_TYPE=1, документ = УПД (SF).
     */
    private const WAIT_RETIRE = 'm.STATUS = 6 and m.RETIRE_REASON = 1 and m.TRANSFER_TYPE = 3 '
        . 'and m.CHZ_SENT_AT is null and m.CHZ_SKIP_AT is null';
    private const WAIT_RETIRE_UPD = 'm.STATUS = 6 and m.RETIRE_REASON = 1 and m.TRANSFER_TYPE = 1 '
        . 'and m.CHZ_SENT_AT is null and m.CHZ_SKIP_AT is null';
    /**
     * Вывод по акту списания — RETIRE_REASON=2, ставит Trade2006 при списании товара.
     * Документ = акт: со склада SPISSKLAD, из магазина SPISSHOP. Причина в ЧЗ — утрата.
     */
    private const WAIT_RETIRE_ACT = 'm.STATUS = 6 and m.RETIRE_REASON = 2 '
        . 'and m.CHZ_SENT_AT is null and m.CHZ_SKIP_AT is null';

    /**
     * Откуда у ждущих вывода кодов документ, в порядке сборки: УПД и акты списания
     * вперёд — их единицы, а счетов маркетплейса сотни, иначе документы покупателям
     * ждали бы разбора завала неделю. Алиас документа всюду doc: по его DATA
     * считается выдержка. У акта выдержки нет — списание не отменяют.
     *   [вид пачки, колонка документа, from с join'ами, условие ожидания, выдержка]
     */
    private const SOURCES = [
        [ChzBatch::KIND_RETIRE_UPD, 'rpf.SFCODE',
            'MARKCODES m join REALPRICEF rpf on rpf.REALPRICEFCODE = m.REALPRICEFCODE '
            . 'join SF doc on doc.SFCODE = rpf.SFCODE',
            self::WAIT_RETIRE_UPD, true],
        [ChzBatch::KIND_RETIRE_ACT, 'm.SPISSKLADCODE',
            'MARKCODES m join SPISSKLAD doc on doc.SPISSKLADCODE = m.SPISSKLADCODE',
            self::WAIT_RETIRE_ACT, false],
        [ChzBatch::KIND_RETIRE_ACT, 'm.SPISSHOPCODE',
            'MARKCODES m join SPISSHOP doc on doc.SPISSHOPCODE = m.SPISSHOPCODE',
            self::WAIT_RETIRE_ACT, false],
        [ChzBatch::KIND_RETIRE, 'rp.SCODE',
            'MARKCODES m join REALPRICE rp on rp.REALPRICECODE = m.REALPRICECODE '
            . 'join S doc on doc.SCODE = rp.SCODE',
            self::WAIT_RETIRE, true],
    ];

    /** Код уже занят незакрытой пачкой — второй раз не собираем. */
    private const NOT_IN_BATCH = 'not exists (select 1 from CHZ_BATCH_KI k join CHZ_BATCH b on b.ID = k.BATCH_ID '
        . "where k.KI = m.KI and b.STATUS in ('READY', 'WAIT', 'SENT', 'ERROR'))";

    /** Статусы ГИС МТ, из которых вывод возможен. Всё прочее — повод снять код с отправки. */
    private const CAN_RETIRE = ['INTRODUCED'];

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
        return array_merge($this->promoteWaiting(), $this->collectRetire(), $this->sendNext(), $this->checkSent());
    }

    /**
     * Сборка пачек вывода из оборота. Коды помечает не этот сервис (продажа
     * маркетплейса — MARKCODE_FBS_SOLD, УПД — свой путь), здесь они только
     * группируются: один документ = один счёт либо одна УПД.
     *
     * Пачка за проход и только когда очередь пуста: спешить некуда, а ровная
     * очередь не плодит документы, если отправка встала. Так же разгребается
     * и накопленное — отдельного разового перегона не нужно.
     */
    private function collectRetire(): array
    {
        if (!config('marking.chz.retire')) {
            return [];
        }
        if (ChzBatch::whereIn('STATUS', [ChzBatch::STATUS_READY, ChzBatch::STATUS_WAIT])->exists()) {
            return [];
        }
        foreach (self::SOURCES as [$kind, $column, $from, $wait, $ripe]) {
            $where = $wait . ' and ' . self::NOT_IN_BATCH . ($ripe ? $this->ripe() : '');
            $lines = $this->collectDoc($kind, $column, $from, $where);
            if ($lines) {
                return $lines;
            }
        }
        $this->noticeOrphans();
        return [];
    }

    /**
     * Списанный код, у которого акт не нашёлся (списание сорвалось на полпути,
     * ссылка осталась на несуществующий акт), в пачку не попадёт никогда —
     * о таких зовём человека, раз в сутки.
     */
    private function noticeOrphans(): void
    {
        $orphans = $this->rows(
            'select m.KI from MARKCODES m where ' . self::WAIT_RETIRE_ACT . ' and ' . self::NOT_IN_BATCH
            . ' and not exists (select 1 from SPISSKLAD sk where sk.SPISSKLADCODE = m.SPISSKLADCODE)'
            . ' and not exists (select 1 from SPISSHOP sh where sh.SPISSHOPCODE = m.SPISSHOPCODE)',
            []
        );
        if ($orphans) {
            $kis = array_map(fn($row) => trim((string)$row->KI), $orphans);
            $this->notify(null, 'Коды списаны, но акт списания не найден — вывести в ЧЗ нечем: '
                . implode('; ', array_slice($kis, 0, 10)), 'orphan-act');
        }
    }

    /**
     * Одна пачка из источника (см. SOURCES): самый старый документ с ждущими кодами.
     * Сборка у всех видов одна, различаются только колонка документа, join и условие.
     */
    private function collectDoc(string $kind, string $column, string $from, string $where): array
    {
        $first = $this->rows(
            "select first 1 {$column} as DOCCODE from {$from} where {$where} order by {$column}",
            []
        );
        if (!$first) {
            return [];
        }
        $docCode = intval($first[0]->DOCCODE);
        $kis = array_map(
            fn($row) => trim((string)$row->KI),
            $this->rows(
                "select m.KI from {$from} where {$column} = ? and {$where} order by m.MARKCODE",
                [$docCode]
            )
        );

        [$kis, $lines] = $this->skipUnsuitable($kis, $kind, $docCode);
        if (!$kis) {
            return $lines;
        }

        // Счёт живёт в SCODE, УПД — в SFCODE; у акта своей колонки нет, он на самих кодах.
        $batch = $this->store(
            $kind,
            $kis,
            $kind === ChzBatch::KIND_RETIRE ? $docCode : null,
            $kind === ChzBatch::KIND_RETIRE_UPD ? $docCode : null
        );
        $lines[] = "пачка №{$batch->ID} ({$kind}, документ {$docCode}): собрана, кодов " . count($kis);
        return $lines;
    }

    /**
     * Выдержка: документ моложе положенного в пачку не берём. Проданное на
     * маркетплейсе неделю ещё отменяют и возвращают, а вернуть выведенный код
     * в оборот дороже, чем подождать. Срок считается от даты документа (алиас
     * doc — счёт S либо УПД SF), она же уходит в ЧЗ как дата вывода.
     *
     * Диалект базы первый: типа DATE в ней нет, арифметика идёт по TIMESTAMP,
     * поэтому не CURRENT_DATE, а CURRENT_TIMESTAMP минус число дней. Срок —
     * целое из конфига, в SQL уходит литералом (параметры тут не нужны).
     */
    private function ripe(): string
    {
        $days = max(0, intval(config('marking.chz.retire_delay_days')));
        return $days ? " and doc.DATA < CURRENT_TIMESTAMP - {$days}" : '';
    }

    /**
     * Сверка с ГИС МТ перед отправкой: код мог быть выведен помимо нас (руками
     * в личном кабинете) или вообще не годиться к выводу. Первых помечаем
     * переданными, вторых снимаем с отправки — и то, и другое молча уходит
     * из очереди, вместо того чтобы каждый раз отбиваться в ЧЗ.
     *
     * Возвращает пригодные КИ и строки для лога. Сервис недоступен — сверку
     * пропускаем: пачка соберётся как есть, отказ разберём по её ошибке.
     */
    private function skipUnsuitable(array $kis, string $kind, int $docCode): array
    {
        try {
            $answer = $this->client->post('codes/info', ['inn' => $this->inn(), 'codes' => $kis]);
        } catch (MarkingException $e) {
            return [$kis, ["сверка кодов документа {$docCode} не удалась — " . $e->getMessage()]];
        }

        // Ни одного статуса в ответе — сверка не состоялась. Иначе весь документ
        // был бы молча снят с отправки из-за молчания сервиса.
        if (!($answer['codes'] ?? [])) {
            return [$kis, ["сверка кодов документа {$docCode}: сервис не вернул ни одного статуса"]];
        }

        [$good, $done, $bad] = self::sortByStatus($kis, $answer['codes'], $this->inn());

        $lines = [];
        if ($done) {
            // Выведен не нами либо ушёл вместе с товаром другому участнику —
            // в обоих случаях цикл кода закрыт, повторять нечего.
            $this->stamp(array_keys($done), 'CHZ_SENT_AT');
            $alien = array_filter($done);
            foreach ($alien as $ki => $reason) {
                // Причина у каждого своя (ИНН владельца), поэтому по коду за раз.
                $this->note([$ki], $reason);
            }
            $closed = count($done) - count($alien);
            if ($closed) {
                $lines[] = "документ {$docCode}: {$closed} код(ов) уже выведены в ЧЗ — отмечены переданными";
            }
            if ($alien) {
                $lines[] = "документ {$docCode}: " . count($alien)
                    . ' код(ов) числятся за другим участником — закрыты без вывода';
            }
        }
        if ($bad) {
            foreach ($bad as $ki => $reason) {
                $this->skip([$ki], $reason);
            }
            $lines[] = "документ {$docCode}: " . count($bad) . ' код(ов) сняты с отправки — ' . reset($bad);
            $this->notify(null, "Документ {$docCode} ({$kind}): Честный знак не даёт вывести коды — "
                . implode('; ', array_slice(array_keys($bad), 0, 10)), 'skip-' . $docCode);
        }
        return [$good, $lines];
    }

    /**
     * Коды пачки, названные в тексте отказа. Не разбираем формат сообщений ЧЗ —
     * ищем в тексте свои же КИ: так работает с любой формулировкой.
     * Чистая функция ради теста.
     */
    public static function blamed(array $kis, string $error): array
    {
        // Ответ ЧЗ приходит как JSON, и кавычка внутри кода в нём экранирована:
        // код 0104711287421810215;VJDtSFR"lMEp в тексте выглядит как ...SFR\"lMEp.
        // Ищем и в исходном тексте, и в снятом с экранирования.
        $plain = str_replace(['\\"', '\\\\'], ['"', '\\'], $error);

        return array_values(array_filter(
            $kis,
            fn($ki) => $ki !== '' && (mb_strpos($error, $ki) !== false || mb_strpos($plain, $ki) !== false)
        ));
    }

    /**
     * Раскладка кодов по ответу ГИС МТ: [пригодные, закрытые, негодные].
     * Закрытые — список «КИ => причина»: выведен помимо нас (причина null)
     * либо числится за другим участником (причина словами). Негодные — «КИ =>
     * причина», она уйдёт в CHZ_SKIP_TEXT и в письмо.
     * Чистая функция: тут вся суть сверки, поэтому она и вынесена отдельно.
     */
    public static function sortByStatus(array $kis, array $cises, string $inn = ''): array
    {
        $answer = [];
        foreach ($cises as $code) {
            $answer[$code['ki'] ?? ''] = $code;
        }

        $good = [];
        $done = [];
        $bad = [];
        foreach ($kis as $ki) {
            $code = $answer[$ki] ?? null;
            $status = $code['status'] ?? null;
            // Владелец в ГИС МТ — не мы: вывести такой код нельзя, ЧЗ ответит
            // «не принадлежит участнику оборота». Ловим до отправки документа.
            $owner = $code['ownerInn'] ?? ($code['raw']['ownerInn'] ?? null);
            $name = $code['raw']['ownerName'] ?? null;

            if ($status === 'RETIRED') {
                $done[$ki] = null;
            } elseif ($inn !== '' && $owner && $owner !== $inn) {
                // Код числится за другим участником: мы продали товар юрлицу,
                // и вместе с ним ушёл код — выводить его теперь не наше дело
                // и не наше право. Считаем закрытым, причину храним словами.
                $done[$ki] = 'Честный знак: код принадлежит другому участнику — ' . $owner
                    . ($name ? " ({$name})" : '') . ' — вывод не требуется';
            } elseif (in_array($status, self::CAN_RETIRE, true)) {
                $good[] = $ki;
            } else {
                $bad[$ki] = 'Честный знак: ' . ($status ?: 'код не найден');
            }
        }
        return [$good, $done, $bad];
    }

    /** Пачка и её коды одной транзакцией: список КИ без пачки — мусор, и наоборот. */
    private function store(string $kind, array $kis, ?int $scode, ?int $sfcode): ChzBatch
    {
        $batch = new ChzBatch();
        $batch->KIND = $kind;
        $batch->CNT = count($kis);
        $batch->SCODE = $scode;
        $batch->SFCODE = $sfcode;
        $batch->STATUS = ChzBatch::STATUS_READY;
        $batch->CREATED_BY = 'chz:outbox';

        // Транзакция по правилам этого драйвера — см. комментарий в retry().
        $connection = DB::connection('firebird');
        $connection->getPdo()->setAttribute(\PDO::ATTR_AUTOCOMMIT, 0);
        $connection->beginTransaction();
        try {
            $batch->save();
            foreach ($kis as $ki) {
                $connection->table('CHZ_BATCH_KI')->insert(['BATCH_ID' => $batch->ID, 'KI' => $ki]);
            }
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        } finally {
            $connection->getPdo()->setAttribute(\PDO::ATTR_AUTOCOMMIT, 1);
        }
        return $batch;
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
            $body = $this->body($batch);
            if (!$body['items']) {
                $this->fail($batch, 'В пачке нет кодов, пригодных к отправке (нет полного КМ или родителя)');
                return ["пачка №{$batch->ID}: отправлять нечего"];
            }
            $answer = $this->client->post(
                $this->path($batch),
                $body,
                ['X-Request-Id' => $this->requestId($batch)]
            );
        } catch (MarkingException $e) {
            // «уже отправлен, ответ не получен» — сервис сам отказывается повторять,
            // и мы тоже: что там на самом деле, знает только ЧЗ.
            $unknown = mb_strpos($e->getMessage(), 'ответ не получен') !== false;
            $this->fail($batch, ($unknown ? self::UNKNOWN : '') . $e->getMessage());
            return ["пачка №{$batch->ID}: отказ — " . $e->getMessage()];
        }

        // Нанесение и деление возвращают отчёт СУЗ, ввод и вывод из оборота —
        // документ True API. Это разные сущности с разными номерами, поэтому и колонки разные.
        $isDoc = $this->isDocument($batch);
        $sent = $isDoc ? ($answer['documents'] ?? []) : ($answer['reports'] ?? []);
        $key = $isDoc ? 'docId' : 'reportId';
        $id = $sent[0][$key] ?? null;
        if ($isDoc) {
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
        return ["пачка №{$batch->ID} ({$batch->KIND}, кодов " . count($body['items']) . "): документ {$id}"];
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
                $text = 'Честный знак отбил отчёт: ' . ($reason ?: 'без причины');
                $this->fail($batch, $text);
                $lines[] = "пачка №{$batch->ID}: отказ ЧЗ" . $this->splitBlame($batch, $text);
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
     * Разбор отказа: ЧЗ проверяет документ целиком, поэтому один негодный код
     * не выпускает всю пачку. В тексте отказа виноватые перечислены поимённо —
     * ищем в нём коды пачки: найденных снимаем с отправки, остальных освобождаем,
     * и они уедут следующей пачкой. Пачка остаётся красной как след разбора.
     *
     * Ни одного кода в тексте нет (ошибка общая) — не трогаем ничего:
     * пусть человек посмотрит сам.
     *
     * Возвращает хвост для строки лога.
     */
    public function splitBlame(ChzBatch $batch, string $error): string
    {
        $kis = $batch->kis();
        $blamed = self::blamed($kis, $error);
        if (!$blamed) {
            return '';
        }

        // Причина у всех виноватых одна — та, что прислала ЧЗ; резать её по кодам
        // не пытаемся: формат сообщений её дело, а не наше.
        $this->skip($blamed, mb_substr($error, 0, 200));

        $rest = array_values(array_diff($kis, $blamed));
        if ($rest) {
            // Освобождаем невиновных: пока они числятся в отбитой пачке,
            // сборщик их не возьмёт.
            foreach (array_chunk($rest, self::CHUNK) as $part) {
                DB::connection('firebird')
                    ->table('CHZ_BATCH_KI')
                    ->where('BATCH_ID', $batch->ID)
                    ->whereIn('KI', $part)
                    ->delete();
            }
            $batch->CNT = count($blamed);
            $batch->save();
        }
        return ': виноватых ' . count($blamed) . ', возвращено в очередь ' . count($rest);
    }

    /**
     * Пачка подтверждена: ставим отметки, которых до нас не ставил никто,
     * и сверяем количество на кодах с тем, что у ЧЗ.
     */
    private function confirm(ChzBatch $batch): ?string
    {
        $kis = $batch->kis();
        if ($this->isRetire($batch)) {
            // Вывод замыкает цикл кода: ЧЗ подтвердила RETIRED — ставим «передан».
            // Ту же отметку раньше ставил человек кликом «Подтвердить» на вкладке «ЧЗ».
            $this->stamp($kis, 'CHZ_SENT_AT');
        } else {
            $this->stamp($kis, 'REPORTED_AT');
            // Деление вводит ребёнка в оборот тем же документом — у нас это поле пустое.
            // У ввода в оборот отметку обычно уже поставила MARKCODES_INTRODUCE_BY_SCODE,
            // но пачка могла прийти и из другого места: stamp заполняет только пустые.
            if ($batch->KIND === ChzBatch::KIND_DIVISION || trim((string)$batch->KIND) === ChzBatch::KIND_INTRO) {
                $this->stamp($kis, 'ENTERED_CIRCULATION_AT');
            }
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
        // У вывода по УПД документ живёт в SFCODE — без него пачка потеряет своё место.
        $new->SFCODE = $batch->SFCODE;
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

    /**
     * Тело запроса целиком. Кроме кодов вывод из оборота требует причину, дату
     * и реквизиты документа — их в очереди тоже нет: берём из счёта либо УПД
     * в момент отправки, как и сами коды.
     */
    private function body(ChzBatch $batch): array
    {
        $kind = trim((string)$batch->KIND);
        if ($kind === ChzBatch::KIND_RETIRE) {
            return $this->retireBody($batch);
        }
        if ($kind === ChzBatch::KIND_RETIRE_UPD) {
            return $this->retireUpdBody($batch);
        }
        if ($kind === ChzBatch::KIND_RETIRE_ACT) {
            return $this->retireActBody($batch);
        }
        return ['inn' => $this->inn(), 'items' => $this->items($batch)];
    }

    /**
     * Вывод по акту списания: причина LOSS (утрата), цена не нужна, первичный
     * документ — сам акт с его номером и датой. Акт у кода один: со склада
     * (SPISSKLAD) либо из магазина (SPISSHOP), пачка собрана по одному из них.
     */
    private function retireActBody(ChzBatch $batch): array
    {
        $rows = $this->rows(
            'select m.KI, m.KM_FULL, COALESCE(sk.SPISSKLADCODE, sh.SPISSHOPCODE) as NUM, '
            . 'COALESCE(sk.DATA, sh.DATA) as DATA from CHZ_BATCH_KI k '
            . 'join MARKCODES m on m.KI = k.KI '
            . 'left join SPISSKLAD sk on sk.SPISSKLADCODE = m.SPISSKLADCODE '
            . 'left join SPISSHOP sh on sh.SPISSHOPCODE = m.SPISSHOPCODE '
            . 'where k.BATCH_ID = ? order by m.MARKCODE',
            [$batch->ID]
        );
        $head = $rows[0] ?? null;
        $date = $this->day($head->DATA ?? null);
        return [
            'inn' => $this->inn(),
            'reason' => 'LOSS',
            'date' => $date,
            'document' => [
                'type' => 'OTHER',
                'name' => 'Акт списания',
                'number' => (string)intval($head->NUM ?? 0),
                'date' => $date,
            ],
            'items' => array_map(fn($row) => ['km' => $this->code($row)], $rows),
        ];
    }

    /**
     * Вывод по продаже маркетплейса: причина DISTANCE, цена обязательна,
     * первичный документ не нужен. Дата — дата счёта: у старых продаж RETIRED_AT
     * это день, когда включили автоматику, а не день продажи.
     */
    private function retireBody(ChzBatch $batch): array
    {
        $rows = $this->rows(
            'select m.KI, m.KM_FULL, rp.PRICE, s.DATA from CHZ_BATCH_KI k '
            . 'join MARKCODES m on m.KI = k.KI '
            . 'join REALPRICE rp on rp.REALPRICECODE = m.REALPRICECODE '
            . 'join S s on s.SCODE = rp.SCODE '
            . 'where k.BATCH_ID = ? order by m.MARKCODE',
            [$batch->ID]
        );
        return [
            'inn' => $this->inn(),
            'reason' => 'DISTANCE',
            'date' => $this->day($rows[0]->DATA ?? null),
            'items' => array_map(fn($row) => [
                'km' => $this->code($row),
                'priceKop' => $this->kopecks($row->PRICE),
            ], $rows),
        ];
    }

    /**
     * Вывод по УПД покупателю, не зарегистрированному в ЧЗ: причина OWN_USE
     * с ИНН покупателя и первичным документом — так ответила поддержка ЧЗ
     * (обращение SR8508987, см. runbook §8).
     */
    private function retireUpdBody(ChzBatch $batch): array
    {
        $rows = $this->rows(
            'select m.KI, m.KM_FULL, rpf.PRICE, sf.NSF, sf.DATA, p.INN from CHZ_BATCH_KI k '
            . 'join MARKCODES m on m.KI = k.KI '
            . 'join REALPRICEF rpf on rpf.REALPRICEFCODE = m.REALPRICEFCODE '
            . 'join SF sf on sf.SFCODE = rpf.SFCODE '
            . 'left join POKUPAT p on p.POKUPATCODE = sf.POKUPATCODE '
            . 'where k.BATCH_ID = ? order by m.MARKCODE',
            [$batch->ID]
        );
        $head = $rows[0] ?? null;
        $date = $this->day($head->DATA ?? null);
        return [
            'inn' => $this->inn(),
            'reason' => 'OWN_USE',
            'date' => $date,
            'buyerInn' => $this->buyerInn($head->INN ?? null),
            'document' => [
                'type' => 'OTHER',
                'name' => 'УПД',
                'number' => trim((string)($head->NSF ?? '')),
                'date' => $date,
            ],
            'items' => array_map(fn($row) => [
                'km' => $this->code($row),
                'priceKop' => $this->kopecks($row->PRICE),
            ], $rows),
        ];
    }

    /**
     * Что шлём как код. Полного КМ у чужого кода (пришёл от поставщика по УПД)
     * нет, а выводить его надо: в документе вывода участвует только КИ, и сервис
     * принимает оба вида.
     */
    private function code(object $row): string
    {
        $km = trim((string)($row->KM_FULL ?? ''));
        return $km !== '' ? $km : trim((string)$row->KI);
    }

    /** Цена строки документа в копейках: ЧЗ хочет целое (30000 = 300.00 руб). */
    private function kopecks($price): int
    {
        return intval(round(floatval($price) * 100));
    }

    /** ЧЗ принимает календарную дату; время в документах вывода всё равно нулевое. */
    private function day($value): string
    {
        return $value ? date('Y-m-d', strtotime((string)$value)) : date('Y-m-d');
    }

    /** В POKUPAT.INN лежит «ИНН/КПП» — сервису нужен только ИНН. */
    private function buyerInn($value): string
    {
        return trim(explode('/', (string)$value)[0]);
    }

    /** Коды пачки для отчётов СУЗ. Коды не хранятся в очереди — собираем из базы. */
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
        $kind = trim((string)$batch->KIND);
        if ($kind === ChzBatch::KIND_INTRO) {
            return 'introduce';
        }
        if ($this->isRetire($batch)) {
            return 'retire';
        }
        return $kind === ChzBatch::KIND_DIVISION ? 'apply/division' : 'apply';
    }

    /** Проверка: у ввода и вывода из оборота документ True API, у остальных отчёт СУЗ. */
    private function checkPath(ChzBatch $batch): string
    {
        return $this->isDocument($batch)
            ? 'document/' . $batch->DOC_UUID
            : 'apply/' . $batch->REPORT_ID;
    }

    /** Вид пачки создаёт документ True API (а не отчёт СУЗ). */
    private function isDocument(ChzBatch $batch): bool
    {
        return trim((string)$batch->KIND) === ChzBatch::KIND_INTRO || $this->isRetire($batch);
    }

    /** Виды вывода — по продаже маркетплейса, по УПД, по акту — отличаются только телом документа. */
    private function isRetire(ChzBatch $batch): bool
    {
        return in_array(trim((string)$batch->KIND), self::RETIRE_KINDS, true);
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
        } elseif (in_array($kind, self::RETIRE_KINDS, true)) {
            $accepted = self::AFTER_RETIRE;
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

    /**
     * Снять коды с отправки в ЧЗ: больше они не попадут ни в одну пачку.
     * Ставится и человеком с экрана, и воркером по ответу ЧЗ. Отметка отдельная
     * от CHZ_SENT_AT: «мы это не отправляем» и «мы это вывели» — разные вещи,
     * иначе потом не разобрать, что выведено на самом деле.
     */
    public function skip(array $kis, string $reason): int
    {
        $done = 0;
        foreach (array_chunk($kis, self::CHUNK) as $part) {
            $in = implode(',', array_fill(0, count($part), '?'));
            $done += DB::connection('firebird')->update(
                "update MARKCODES set CHZ_SKIP_AT = CURRENT_TIMESTAMP, CHZ_SKIP_TEXT = ? "
                . "where KI in ({$in}) and CHZ_SENT_AT is null",
                array_merge([mb_substr($reason, 0, 200)], $part)
            );
        }
        return $done;
    }

    /** Вернуть снятые коды в очередь: причина стирается вместе с отметкой. */
    public function unskip(array $kis): int
    {
        $done = 0;
        foreach (array_chunk($kis, self::CHUNK) as $part) {
            $in = implode(',', array_fill(0, count($part), '?'));
            $done += DB::connection('firebird')->update(
                "update MARKCODES set CHZ_SKIP_AT = null, CHZ_SKIP_TEXT = null where KI in ({$in})",
                $part
            );
        }
        return $done;
    }

    /**
     * Пометка на закрытом коде: почему вывод не потребовался. CHZ_SKIP_AT при
     * этом не ставится — код не снят с отправки, он закрыт; иначе «не выводим»
     * и «выводить нечего» смешались бы в одну кучу.
     */
    private function note(array $kis, string $reason): void
    {
        foreach (array_chunk($kis, self::CHUNK) as $part) {
            $in = implode(',', array_fill(0, count($part), '?'));
            DB::connection('firebird')->update(
                "update MARKCODES set CHZ_SKIP_TEXT = ? where KI in ({$in}) and CHZ_SKIP_AT is null",
                array_merge([mb_substr($reason, 0, 200)], $part)
            );
        }
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
