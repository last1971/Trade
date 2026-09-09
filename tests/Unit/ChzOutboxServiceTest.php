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
        // Выведен помимо нас — не ошибка: код просто отмечается переданным.
        $this->assertSame(['ki-out'], $done);
        // Не введён в оборот и вовсе неизвестный ЧЗ — снимаем с отправки с причиной.
        $this->assertSame(
            ['ki-emitted' => 'Честный знак: EMITTED', 'ki-unknown' => 'Честный знак: код не найден'],
            $bad
        );
    }
}
