<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PromelecFactureImport implements WithHeadingRow, WithCustomCsvSettings, WithCustomValueBinder
{
    use HeaderNameTrait;

    public function getCsvSettings(): array
    {
        return [
            //'input_encoding' => 'Windows-1251',
            'delimiter' => ';',
        ];
    }
}
