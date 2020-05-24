<?php

namespace App\Imports;

use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

trait HeaderNameTrait
{
    private $nameHeaders = ['наименование', 'название', 'имя'];

    private $quantityHeaders = ['количество', 'кол.', 'кол', 'кол-во', 'кол.-во', 'qnt', 'quan'];

    private $priceHeaders = ['цена', 'цена товара'];

    private $amountWithVat = ['сумма', 'валютная сумма'];

    private function setHeaderName(Cell $cell, $value)
    {
        $val = Str::lower($value);
        if (array_search($val, $this->nameHeaders) !== FALSE) {
            $cell->setValueExplicit("name", DataType::TYPE_STRING);
        } else if (array_search($val, $this->quantityHeaders) !== FALSE) {
            $cell->setValueExplicit("priceWithVat", DataType::TYPE_STRING);
        } else if (array_search($val, $this->priceHeaders) !== FALSE) {
            $cell->setValueExplicit("amountWithVat", DataType::TYPE_STRING);
        }
    }
}
