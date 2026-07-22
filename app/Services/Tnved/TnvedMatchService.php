<?php

declare(strict_types=1);

namespace App\Services\Tnved;

use App\Services\AI\AIRequest;
use App\Services\AI\AIService;
use App\Tnved;

/**
 * Подбор кода ТН ВЭД по описанию товара.
 *
 * Схема (семантику даёт модель, коды — справочник):
 *  1. модель называет 1-3 товарные позиции (4 знака) по описанию;
 *  2. из tnved достаём реальные 10-значные коды этих позиций (кандидаты);
 *  3. модель выбирает ОДИН код из кандидатов + уверенность;
 *  4. низкая уверенность → повтор шага 3 на более сильной модели (эскалация);
 *  5. сверяем выбранный код по справочнику и отдаём расшифровку + тариф.
 */
final class TnvedMatchService
{
    /** Модель для шага 1 (определение позиции) и основного выбора. */
    private const MODEL_MAIN = 'claude-sonnet-5';

    /** Модель эскалации при низкой уверенности. */
    private const MODEL_ESCALATE = 'claude-opus-4-8';

    /** Порог уверенности: ниже — эскалация; >= — apply=true. */
    private const CONFIDENCE_THRESHOLD = 80;

    /** Ограничение числа кандидатов, отдаваемых модели (экономия токенов). */
    private const MAX_CANDIDATES = 150;

    /** Максимум товарных позиций в отборе. */
    private const MAX_HEADINGS = 4;

    public function __construct(private AIService $ai)
    {
    }

    /**
     * @return array{
     *   found: bool, code?: string, name?: string, tariff?: string|null,
     *   confidence?: int, apply?: bool, reason?: string, model?: string,
     *   headings?: array<int,string>, candidates?: int
     * }
     */
    public function match(string $name, ?string $category = null, ?string $case = null, ?string $maker = null): array
    {
        $desc = $this->describe($name, $category, $case, $maker);

        // Шаг 1 — позиции от модели, отфильтрованные по реально существующим в справочнике
        $headings = $this->existingHeadings($this->proposeHeadings($desc));

        // Страховка: модель промахнулась — лексический отбор позиций по наименованию
        if (empty($headings)) {
            $headings = $this->lexicalHeadings($name);
        }
        if (empty($headings)) {
            return ['found' => false, 'reason' => 'не удалось определить товарную позицию', 'headings' => []];
        }

        // Шаг 2 — кандидаты из справочника
        $candidates = Tnved::whereIn('heading', $headings)
            ->orderBy('code')
            ->limit(self::MAX_CANDIDATES)
            ->get(['code', 'name', 'tariff']);

        // Шаг 3 — выбор кода моделью
        $pick = $this->pick($desc, $candidates, self::MODEL_MAIN);

        // Шаг 4 — эскалация при низкой уверенности
        if ((int) ($pick['confidence'] ?? 0) < self::CONFIDENCE_THRESHOLD) {
            $pick = $this->pick($desc, $candidates, self::MODEL_ESCALATE);
        }

        // Шаг 5 — сверка выбранного кода по справочнику
        $row = Tnved::where('code', (string) ($pick['code'] ?? ''))->first();
        if ($row === null) {
            return [
                'found' => false,
                'reason' => 'модель вернула код вне справочника',
                'headings' => $headings,
                'candidates' => $candidates->count(),
            ];
        }

        $confidence = (int) ($pick['confidence'] ?? 0);

        return [
            'found' => true,
            'code' => $row->code,
            'name' => $row->name,
            'tariff' => $row->tariff,
            'confidence' => $confidence,
            'apply' => $confidence >= self::CONFIDENCE_THRESHOLD,
            'reason' => (string) ($pick['reason'] ?? ''),
            'model' => (string) ($pick['_model'] ?? ''),
            'headings' => $headings,
            'candidates' => $candidates->count(),
        ];
    }

    private function describe(string $name, ?string $category, ?string $case, ?string $maker): string
    {
        $lines = ['Название: ' . $name];
        if ($category) {
            $lines[] = 'Категория: ' . $category;
        }
        if ($case) {
            $lines[] = 'Корпус: ' . $case;
        }
        if ($maker) {
            $lines[] = 'Производитель: ' . $maker;
        }

        return implode("\n", $lines);
    }

    /**
     * Шаг 1: модель предлагает товарные позиции (4-значные коды).
     *
     * @return array<int,string>
     */
    private function proposeHeadings(string $desc): array
    {
        $static = <<<'TXT'
        Ты эксперт по классификации товаров по ТН ВЭД ЕАЭС.
        По описанию товара определи наиболее вероятные ТОВАРНЫЕ ПОЗИЦИИ — 4-значные коды ТН ВЭД.
        Верни строго JSON без пояснений: {"headings": ["8542", "8504"]}.
        Только 4-значные коды, максимум 3, самые вероятные первыми. Разговорные названия
        переводи в официальную номенклатуру (например «микросхема» → интегральные схемы 8542,
        «блок питания» → статические преобразователи 8504).
        TXT;

        $req = new AIRequest(
            staticPrompt: $static,
            dynamicPrompt: $desc,
            maxTokens: 200,
            thinking: 'disabled',
        );

        $data = $this->ai->generate('claude', self::MODEL_MAIN, $req)->parseJson();
        $headings = $data['headings'] ?? [];

        $out = [];
        foreach ((array) $headings as $h) {
            $h = preg_replace('/\D/', '', (string) $h);
            if (strlen($h) === 4) {
                $out[] = $h;
            }
        }

        return array_slice(array_values(array_unique($out)), 0, self::MAX_HEADINGS);
    }

    /**
     * Оставить только позиции, реально присутствующие в справочнике.
     *
     * @param array<int,string> $headings
     * @return array<int,string>
     */
    private function existingHeadings(array $headings): array
    {
        if (empty($headings)) {
            return [];
        }

        return Tnved::whereIn('heading', $headings)
            ->distinct()
            ->pluck('heading')
            ->all();
    }

    /**
     * Страховка: подобрать позиции лексически — по словам наименования.
     *
     * @return array<int,string>
     */
    private function lexicalHeadings(string $name): array
    {
        $words = array_filter(
            preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($name)) ?: [],
            fn ($w) => mb_strlen($w) >= 4
        );
        if (empty($words)) {
            return [];
        }

        $q = Tnved::query();
        foreach ($words as $w) {
            $q->orWhere('name', 'like', '%' . $w . '%');
        }

        return $q->selectRaw('heading, COUNT(*) as cnt')
            ->groupBy('heading')
            ->orderByDesc('cnt')
            ->limit(self::MAX_HEADINGS)
            ->pluck('heading')
            ->all();
    }

    /**
     * Шаг 3: модель выбирает код из кандидатов.
     *
     * @param \Illuminate\Support\Collection<int,\App\Tnved> $candidates
     * @return array<string,mixed>
     */
    private function pick(string $desc, $candidates, string $model): array
    {
        $static = <<<'TXT'
        Ты эксперт по ТН ВЭД ЕАЭС. Из списка КАНДИДАТОВ ниже выбери ОДИН код,
        который точнее всего соответствует товару. Выбирай ТОЛЬКО из списка — не придумывай коды.
        Верни строго JSON без пояснений:
        {"code": "10 цифр", "confidence": 0-100, "apply": true|false, "reason": "кратко почему"}
        confidence — насколько ты уверен в выборе. apply=true только если confidence >= 80.
        TXT;

        $lines = [];
        foreach ($candidates as $c) {
            $lines[] = $c->code . ' | ' . $c->name;
        }

        $dynamic = "Товар:\n" . $desc . "\n\nКандидаты (код | наименование):\n" . implode("\n", $lines);

        $req = new AIRequest(
            staticPrompt: $static,
            dynamicPrompt: $dynamic,
            maxTokens: 400,
            thinking: $model === self::MODEL_ESCALATE ? 'adaptive' : 'disabled',
        );

        $data = $this->ai->generate('claude', $model, $req)->parseJson();
        $data['_model'] = $model;

        return $data;
    }
}
