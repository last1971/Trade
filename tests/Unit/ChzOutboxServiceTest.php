<?php

namespace Tests\Unit;

use App\ChzBatch;
use App\Services\Marking\ChzOutboxService;
use PHPUnit\Framework\TestCase;

/**
 * Проверка пачки: статус отчёта в СУЗ навсегда SENT, поэтому судим по статусам
 * самих кодов в ГИС МТ. Жизненный цикл: EMITTED -> APPLIED -> INTRODUCED -> RETIRED.
 */
class ChzOutboxServiceTest extends TestCase
{
    private function cis(string $ki, ?string $status): array
    {
        return ['cisInfo' => ['requestedCis' => $ki, 'status' => $status]];
    }

    public function testDivisionConfirmedOnlyFromIntroducedOnwards()
    {
        $kis = ['ki-1', 'ki-2'];
        $done = [$this->cis('ki-1', 'INTRODUCED'), $this->cis('ki-2', 'RETIRED')];
        $this->assertSame([], ChzOutboxService::pending($kis, $done, ChzBatch::KIND_DIVISION));

        // APPLIED для деления мало: ребёнок должен оказаться в обороте.
        $half = [$this->cis('ki-1', 'INTRODUCED'), $this->cis('ki-2', 'APPLIED')];
        $this->assertSame(['ki-2 = APPLIED'], ChzOutboxService::pending($kis, $half, ChzBatch::KIND_DIVISION));
    }

    public function testApplyConfirmedFromAppliedOnwards()
    {
        $kis = ['ki-1', 'ki-2', 'ki-3'];
        $cises = [
            $this->cis('ki-1', 'APPLIED'),
            $this->cis('ki-2', 'INTRODUCED'),
            $this->cis('ki-3', 'EMITTED'),
        ];
        // Код, уехавший дальше по жизненному циклу, ошибкой не считается;
        // EMITTED — ГИС МТ ещё не обработала, ждём.
        $this->assertSame(['ki-3 = EMITTED'], ChzOutboxService::pending($kis, $cises, ChzBatch::KIND_APPLY));
    }

    public function testIntroConfirmedOnlyFromIntroducedOnwards()
    {
        $kis = ['ki-1', 'ki-2'];
        $done = [$this->cis('ki-1', 'INTRODUCED'), $this->cis('ki-2', 'RETIRED')];
        $this->assertSame([], ChzOutboxService::pending($kis, $done, ChzBatch::KIND_INTRO));

        // Нанесён, но в оборот не введён — документ ещё не отработал.
        $half = [$this->cis('ki-1', 'INTRODUCED'), $this->cis('ki-2', 'APPLIED')];
        $this->assertSame(['ki-2 = APPLIED'], ChzOutboxService::pending($kis, $half, ChzBatch::KIND_INTRO));
    }

    public function testCodeMissingInAnswerStaysPending()
    {
        $pending = ChzOutboxService::pending(['ki-1'], [], ChzBatch::KIND_APPLY);
        $this->assertSame(['ki-1 = нет ответа'], $pending);
    }

    public function testPlainCisAnswerWithoutWrapperIsUnderstood()
    {
        $cises = [['cis' => 'ki-1', 'status' => 'INTRODUCED']];
        $this->assertSame([], ChzOutboxService::pending(['ki-1'], $cises, ChzBatch::KIND_DIVISION));
    }

    public function testRetireConfirmedOnlyByRetired()
    {
        $kis = ['ki-1', 'ki-2'];
        $done = [$this->cis('ki-1', 'RETIRED'), $this->cis('ki-2', 'RETIRED')];
        $this->assertSame([], ChzOutboxService::pending($kis, $done, ChzBatch::KIND_RETIRE));

        // Документ вывода принят, но код всё ещё в обороте — ГИС МТ не отработала.
        $half = [$this->cis('ki-1', 'RETIRED'), $this->cis('ki-2', 'INTRODUCED')];
        $this->assertSame(['ki-2 = INTRODUCED'], ChzOutboxService::pending($kis, $half, ChzBatch::KIND_RETIRE));
        $this->assertSame(['ki-2 = INTRODUCED'], ChzOutboxService::pending($kis, $half, ChzBatch::KIND_RETIRE_UPD));
    }

    public function testBlamedFindsCodesNamedInRejection()
    {
        // Текст отказа ЧЗ как есть (пачка №41 от 09.09): виноватые названы поимённо.
        $error = 'Честный знак отбил отчёт: ["11: Код идентификации 0100400001492086215yMe+ulo=Tnhs7dBY%pa '
            . 'не принадлежит участнику оборота."]';
        $kis = ['0100400001492086215yMe+ulo=Tnhs7dBY%pa', '0100400001492086215W3WbRHqRsvEl1WCYKUJ'];

        // Назван один — второй невиновен и должен вернуться в очередь.
        $this->assertSame([$kis[0]], ChzOutboxService::blamed($kis, $error));
        // Ошибка без кодов (связь, подпись, общий отказ) — виноватых не назначаем.
        $this->assertSame([], ChzOutboxService::blamed($kis, 'Честный знак отбил отчёт: без причины'));
    }

    public function testBlamedFindsCodeWithQuoteInside()
    {
        // Пачка №158 от 09.09: в коде есть кавычка, а ответ ЧЗ — JSON,
        // где она приходит экранированной. Без снятия экранирования виновник терялся.
        $ki = '0104711287421810215;VJDtSFR"lMEpgXrdFl';
        $error = 'Честный знак отбил отчёт: ["11: Код идентификации '
            . '0104711287421810215;VJDtSFR\\"lMEpgXrdFl не принадлежит участнику оборота."]';

        $this->assertSame([$ki], ChzOutboxService::blamed([$ki], $error));
    }

    public function testSortByStatusClosesCodesOfAnotherParticipant()
    {
        // Живой случай 09.09: коды счёта 14896 числились за ООО «БИС», и ЧЗ отбила
        // документ целиком с «не принадлежит участнику оборота». Ловим до отправки —
        // и не в карантин, а закрываем: код ушёл юрлицу вместе с товаром, выводить
        // его теперь не нам.
        $cises = [
            ['ki' => 'ki-our', 'status' => 'INTRODUCED', 'ownerInn' => '7017364619'],
            ['ki' => 'ki-alien', 'status' => 'INTRODUCED', 'ownerInn' => '3435100567',
                'raw' => ['ownerName' => 'ООО "БИС"']],
        ];
        [$good, $done, $bad] = ChzOutboxService::sortByStatus(
            ['ki-our', 'ki-alien'],
            $cises,
            '7017364619'
        );

        $this->assertSame(['ki-our'], $good);
        $this->assertSame(
            ['ki-alien' => 'Честный знак: код принадлежит другому участнику — 3435100567 (ООО "БИС")'
                . ' — вывод не требуется'],
            $done
        );
        $this->assertSame([], $bad);
    }

    public function testSortByStatusSplitsSuitableSpentAndHopeless()
    {
        $kis = ['ki-in', 'ki-out', 'ki-emitted', 'ki-unknown'];
        $cises = [
            ['ki' => 'ki-in', 'status' => 'INTRODUCED'],
            ['ki' => 'ki-out', 'status' => 'RETIRED'],
            ['ki' => 'ki-emitted', 'status' => 'EMITTED'],
        ];
        [$good, $done, $bad] = ChzOutboxService::sortByStatus($kis, $cises);

        $this->assertSame(['ki-in'], $good);
        // Выведен помимо нас — не ошибка: код просто отмечается переданным,
        // причина ему не нужна (отсюда null).
        $this->assertSame(['ki-out' => null], $done);
        // Не введён в оборот и вовсе неизвестный ЧЗ — снимаем с отправки с причиной.
        $this->assertSame(
            ['ki-emitted' => 'Честный знак: EMITTED', 'ki-unknown' => 'Честный знак: код не найден'],
            $bad
        );
    }
}
