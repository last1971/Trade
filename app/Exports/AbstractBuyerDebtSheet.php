<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * База для листов отчёта по долгам: общий конструктор, набор концернов и вставка строки ИТОГО.
 * Конкретные листы реализуют title()/headings()/array()/columnFormats()/registerEvents().
 */
abstract class AbstractBuyerDebtSheet implements FromArray, WithHeadings, WithColumnFormatting, WithEvents, WithTitle, ShouldAutoSize
{
    /** Числовой формат с разделителем тысяч. */
    protected const NUM = NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;

    public function __construct(protected array $report)
    {
    }

    /**
     * Строка ИТОГО: подпись в $labelCol и формулы =SUM по $sumCols (живые, пересчитываются).
     *
     * @param string[] $sumCols например ['D','E','F']
     */
    protected function totalsRow(Worksheet $sheet, int $first, int $last, string $labelCol, array $sumCols, string $label = 'ИТОГО'): void
    {
        $total = $last + 1;
        $sheet->setCellValue("{$labelCol}{$total}", $label);
        foreach ($sumCols as $col) {
            $sheet->setCellValue("{$col}{$total}", "=SUM({$col}{$first}:{$col}{$last})");
        }
        $sheet->getStyle("{$labelCol}{$total}:" . end($sumCols) . $total)->getFont()->setBold(true);
    }
}
