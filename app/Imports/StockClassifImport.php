<?php

namespace App\Imports;

use App\Good;
use App\GoodClassif;
use App\GoodName;
use App\Services\Marking\GoodClassifyService;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Загрузка разметки из файла «Разгребание склада» (StockClassifExport, заполненный руками).
 * Отвечает только за файл: столбцы по заголовкам, сверка названия с базой (коды опта и
 * магазина разные — чужой файл не должен тихо разметить чужие товары), «без изменений».
 * Правила вердикта и запись — GoodClassifyService::setVerdict.
 */
class StockClassifImport
{
    private const COLUMNS = [
        'code' => 'Код',
        'name' => 'Наименование',
        'tnved' => 'ТНВЭД',
        'okpd2' => 'ОКПД2',
        'mark' => 'Подлежит ЧЗ',
    ];

    public function __construct(private GoodClassifyService $service)
    {
    }

    /**
     * @return array{rows: int, applied: int, unchanged: int, skipped: int, errors: array<int, array{GOODSCODE: int|string, message: string}>}
     */
    public function apply(UploadedFile $file): array
    {
        $sheet = Excel::toArray(new \stdClass(), $file)[0] ?? [];
        $index = $this->columns(array_shift($sheet) ?? []);

        $rows = [];
        foreach ($sheet as $line) {
            $code = $line[$index['code']] ?? null;
            if ($code === null || $code === '') {
                continue;
            }
            $rows[] = [
                'code' => intval($code),
                'name' => (string) ($line[$index['name']] ?? ''),
                'tnved' => $this->tnved($line[$index['tnved']] ?? null),
                'okpd2' => trim((string) ($line[$index['okpd2']] ?? '')),
                'mark' => mb_strtolower(trim((string) ($line[$index['mark']] ?? ''))),
            ];
        }

        $result = ['rows' => count($rows), 'applied' => 0, 'unchanged' => 0, 'skipped' => 0, 'errors' => []];
        [$goods, $primaries] = $this->load(array_column($rows, 'code'));

        foreach ($rows as $row) {
            if ($row['mark'] === '') {
                $result['skipped']++;
                continue;
            }
            $error = $this->check($row, $goods[$row['code']] ?? null);
            if ($error) {
                $result['errors'][] = ['GOODSCODE' => $row['code'], 'message' => $error];
                continue;
            }
            $markRequired = $row['mark'] === 'да' ? 1 : 0;
            $primary = $primaries[$row['code']] ?? null;
            if ($primary
                && intval($primary->MARK_REQUIRED) === $markRequired
                && $primary->TNVED === ($row['tnved'] ?: $primary->TNVED)
                && $primary->OKPD2 === ($row['okpd2'] ?: $primary->OKPD2)) {
                $result['unchanged']++;
                continue;
            }
            try {
                $this->service->setVerdict($row['code'], $markRequired, $row['tnved'] ?: null, $row['okpd2'] ?: null);
                $result['applied']++;
            } catch (\Exception $e) {
                $result['errors'][] = ['GOODSCODE' => $row['code'], 'message' => $e->getMessage()];
            }
        }

        return $result;
    }

    /**
     * Номера столбцов по заголовкам первой строки — порядок и лишние столбцы не важны.
     */
    private function columns(array $header): array
    {
        $header = array_map(fn($v) => mb_strtolower(trim((string) $v)), $header);
        $index = [];
        foreach (self::COLUMNS as $key => $title) {
            $found = array_search(mb_strtolower($title), $header, true);
            if ($found === false) {
                throw new \InvalidArgumentException("В файле нет столбца «{$title}»");
            }
            $index[$key] = $found;
        }
        return $index;
    }

    /**
     * ТН ВЭД, введённый руками, Excel хранит числом — ведущий ноль теряется; возвращаем.
     */
    private function tnved($value): string
    {
        if (is_int($value) || is_float($value)) {
            return str_pad(sprintf('%.0f', $value), 10, '0', STR_PAD_LEFT);
        }
        return trim((string) $value);
    }

    private function check(array $row, ?Good $good): ?string
    {
        if (!in_array($row['mark'], ['да', 'нет'], true)) {
            return "«Подлежит ЧЗ» — только «да» или «нет», в файле «{$row['mark']}»";
        }
        if (!$good || $good->HIDDEN) {
            return 'товара с таким кодом нет в этой базе';
        }
        $dbName = $good->name->NAME ?? '';
        if (GoodName::normalize($dbName) !== GoodName::normalize($row['name'])) {
            return "название не совпадает с базой («{$dbName}») — файл из другой базы?";
        }
        return null;
    }

    /**
     * Товары и основные строки классификации по кодам файла — порциями (IN у Firebird ≤ 1500).
     */
    private function load(array $codes): array
    {
        $goods = [];
        $primaries = [];
        foreach (array_chunk(array_unique($codes), 1000) as $chunk) {
            foreach (Good::query()->select(['GOODSCODE', 'NAMECODE', 'HIDDEN'])->with('name:NAMECODE,NAME')
                         ->whereIn('GOODSCODE', $chunk)->get() as $good) {
                $goods[intval($good->GOODSCODE)] = $good;
            }
            foreach (GoodClassif::query()->where('IS_PRIMARY', 1)->whereIn('GOODSCODE', $chunk)->get() as $row) {
                $primaries[intval($row->GOODSCODE)] = $row;
            }
        }
        return [$goods, $primaries];
    }
}
