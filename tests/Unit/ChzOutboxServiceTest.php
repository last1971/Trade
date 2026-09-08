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
}
