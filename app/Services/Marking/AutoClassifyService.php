<?php

namespace App\Services\Marking;

use App\Good;
use App\Services\Tnved\TnvedMatchService;

/**
 * Ядро авто-классификации ОДНОГО товара: по данным товара подбираем код ТН ВЭД,
 * по справочнику маркировки решаем подлежит/не подлежит и выбираем ОКПД2.
 *
 * Только подбор (без записи вердикта) — писать через GoodClassifyService::setVerdict
 * решает вызывающий. Единое ядро для команды tnved:classify и фоновой AI-пачки.
 */
class AutoClassifyService
{
    public function __construct(
        private TnvedMatchService $matcher,
        private MarkingDictService $marking
    ) {
    }

    /**
     * Подобрать классификацию товара. Статусы: ok | no_name | not_found | low_confidence.
     *
     * @return array{
     *   goodscode: int, name: string, status: string,
     *   tnved: ?string, tnved_name: ?string, mark_required: ?int, okpd2: ?string,
     *   confidence: ?int, model: ?string, reason: ?string, prim: ?string
     * }
     */
    public function classifyOne(int $goodscode, int $threshold): array
    {
        $base = [
            'goodscode' => $goodscode,
            'name' => '—',
            'status' => 'no_name',
            'tnved' => null,
            'tnved_name' => null,
            'mark_required' => null,
            'okpd2' => null,
            'confidence' => null,
            'model' => null,
            'reason' => null,
            'prim' => null,
        ];

        $good = Good::with(['name', 'category'])->find($goodscode);
        $name = (string) optional($good?->name)->NAME;
        if ($name === '') {
            return $base;
        }
        $base['name'] = $name;

        $category = optional($good->category)->CATEGORY;
        $case = $good->BODY ?: null;
        $maker = $good->PRODUCER ?: null;

        $res = $this->matcher->match($name, $category, $case, $maker);
        if (!($res['found'] ?? false)) {
            return array_merge($base, ['status' => 'not_found', 'reason' => $res['reason'] ?? null]);
        }

        $tnved = (string) $res['code'];
        $conf = (int) $res['confidence'];
        $base = array_merge($base, [
            'tnved' => $tnved,
            'tnved_name' => $res['name'] ?? null,
            'confidence' => $conf,
            'model' => $res['model'] ?? null,
            'reason' => $res['reason'] ?? null,
        ]);

        if ($conf < $threshold) {
            return array_merge($base, ['status' => 'low_confidence']);
        }

        // Подлежит = код есть в справочнике маркируемых; ОКПД2 — моделью из вариантов.
        $markRequired = $this->marking->isMarkRequired($tnved) ? 1 : 0;
        $okpd2 = $markRequired
            ? $this->matcher->chooseOkpd2($this->marking->okpd2Options($tnved), $name, $category, $case, $maker)
            : null;

        return array_merge($base, [
            'status' => 'ok',
            'mark_required' => $markRequired,
            'okpd2' => $okpd2,
            'prim' => 'авто-подбор ИИ, уверенность ' . $conf . '%, модель ' . ($res['model'] ?? ''),
        ]);
    }
}
