<?php

namespace App\Services\Marking;

/**
 * Единственный читатель справочника маркируемых ТН ВЭД → ОКПД2
 * (resources/js/data/tnvedMarking.json). Отвечает на «подлежит ли код
 * маркировке» и «какие ОКПД2 у кода». Один источник и для бэка (команда,
 * авто-классификация), и для фронта (через API) — второй копии логики нет.
 *
 * Формат записи справочника: {c: код10, n: наименование, o: [{c: окпд2, n: ...}]}.
 */
class MarkingDictService
{
    /** @var array<string, array{c: string, n: string, o: array<int, array{c: string, n: string}>}>|null */
    private ?array $map = null;

    /**
     * Весь справочник как есть (для дропдауна на фронте).
     *
     * @return array<int, array{c: string, n: string, o: array<int, array{c: string, n: string}>}>
     */
    public function all(): array
    {
        return array_values($this->map());
    }

    /**
     * Подлежит ли товар с этим кодом ТН ВЭД маркировке = код есть в справочнике.
     */
    public function isMarkRequired(string $code): bool
    {
        return isset($this->map()[$code]);
    }

    /**
     * Варианты ОКПД2 для кода (пусто, если кода нет в справочнике).
     *
     * @return array<int, array{c: string, n: string}>
     */
    public function okpd2Options(string $code): array
    {
        return $this->map()[$code]['o'] ?? [];
    }

    /**
     * Наименование маркируемого кода из справочника (null, если кода нет).
     */
    public function name(string $code): ?string
    {
        return $this->map()[$code]['n'] ?? null;
    }

    /**
     * Справочник, проиндексированный по коду. Читаем файл один раз на процесс.
     *
     * @return array<string, array{c: string, n: string, o: array<int, array{c: string, n: string}>}>
     */
    private function map(): array
    {
        if ($this->map === null) {
            $path = resource_path('js/data/tnvedMarking.json');
            $data = json_decode((string) file_get_contents($path), true) ?: [];
            $this->map = [];
            foreach ($data as $entry) {
                $this->map[$entry['c']] = $entry;
            }
        }

        return $this->map;
    }
}
