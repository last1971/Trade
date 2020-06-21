<?php

namespace App\Imports;

use App\Helpers\VatHelper;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class CompelFactureImport implements WithHeadingRow, WithCustomCsvSettings, WithCustomValueBinder
{

    public function getCsvSettings(): array
    {
        return [
            'input_encoding' => 'Windows-1251',
            'delimiter' => ';',
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        if ($cell->getRow() === 1) {
            switch ($cell->getColumn()) {
                case 'D':
                    $cell->setValueExplicit('name', DataType::TYPE_STRING);
                    break;
                case 'E':
                    $cell->setValueExplicit('producer', DataType::TYPE_STRING);
                    break;
                case 'F':
                    $cell->setValueExplicit('case', DataType::TYPE_STRING);
                    break;
                case 'G':
                    $cell->setValueExplicit('quantity', DataType::TYPE_STRING);
                    break;
                case 'H':
                    $cell->setValueExplicit('amount', DataType::TYPE_STRING);
                    break;
                case 'I':
                    $cell->setValueExplicit('price', DataType::TYPE_STRING);
                    break;
                case 'K':
                    $cell->setValueExplicit('declaration', DataType::TYPE_STRING);
                    break;
                case 'L':
                    $cell->setValueExplicit('country', DataType::TYPE_STRING);
                    break;
                default:
                    $cell->setValueExplicit($value, DataType::TYPE_STRING);
            }
        } else {
            $str = substr($value, 2, -1);
            $num = str_replace(',', '.', mb_ereg_replace("\s", "", $str));
            switch ($cell->getColumn()) {
                case 'G':
                    $cell->setValueExplicit(intval($num), DataType::TYPE_NUMERIC);
                    break;
                case 'H':
                case 'I':
                    $cell->setValueExplicit(
                        $num * (1 + VatHelper::get() / 100),
                        DataType::TYPE_NUMERIC
                    );
                    break;
                default:
                    $cell->setValueExplicit($str, DataType::TYPE_STRING);
            }
        }
        return true;
    }
}
