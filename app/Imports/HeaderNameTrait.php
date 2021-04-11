<?php

namespace App\Imports;

use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

trait HeaderNameTrait
{
    private $nameHeaders = ['наименование', 'название', 'имя', 'товар', 'name'];

    private $quantityHeaders = ['количество', 'кол.', 'кол', 'кол-во', 'кол.-во', 'qnt', 'quan', 'quant'];

    private $priceHeaders = ['цена', 'цена товара', 'price'];

    private $amountHeaders = ['сумма', 'валютная сумма', 'amount'];

    private $multiplicityHeaders = ['кратность', 'Цена указана за ... шт.'];

    private $countryHeaders = ['страна происхождения', 'страна'];

    private $declarationHeaders = ['код таможенной декларации', 'гтд'];

    private $producerHeaders = ['производитель', 'producer'];

    private $caseHeaders = ['корпус'];

    private $lastMultiplicity = 1;

    private $multiplicityColumn = '';

    private $priceColumn;

    private $quantityColumn;

    private $amountColumn;

    public function bindValue(Cell $cell, $value)
    {
        $val = Str::lower($value);
        if ($cell->getRow() === 1) {
            if (array_search($val, $this->nameHeaders) !== FALSE) {
                $cell->setValueExplicit("name", DataType::TYPE_STRING);
            } else if (array_search($val, $this->quantityHeaders) !== FALSE) {
                $cell->setValueExplicit("quantity", DataType::TYPE_STRING);
                $this->quantityColumn = $cell->getColumn();
            } else if (array_search($val, $this->priceHeaders) !== FALSE) {
                $cell->setValueExplicit("price", DataType::TYPE_STRING);
                $this->priceColumn = $cell->getColumn();
            } else if (array_search($val, $this->amountHeaders) !== FALSE) {
                $cell->setValueExplicit("amount", DataType::TYPE_STRING);
                $this->amountColumn = $cell->getColumn();
            } else if (array_search($val, $this->multiplicityHeaders) !== FALSE) {
                $cell->setValueExplicit("multiplicity", DataType::TYPE_STRING);
                $this->multiplicityColumn = $cell->getColumn();
            } else if (array_search($val, $this->countryHeaders) !== FALSE) {
                $cell->setValueExplicit("country", DataType::TYPE_STRING);
            } else if (array_search($val, $this->declarationHeaders) !== FALSE) {
                $cell->setValueExplicit("declaration", DataType::TYPE_STRING);
            } else if (array_search($val, $this->producerHeaders) !== FALSE) {
                $cell->setValueExplicit("producer", DataType::TYPE_STRING);
            } else if (array_search($val, $this->caseHeaders) !== FALSE) {
                $cell->setValueExplicit("case", DataType::TYPE_STRING);
            } else {
                $cell->setValueExplicit($value, DataType::TYPE_STRING);
            }
        } else {
            if ($cell->getColumn() === $this->multiplicityColumn) {
                $this->lastMultiplicity = intval($value);
                $cell->setValueExplicit($value, DataType::TYPE_NUMERIC);
            } else if ($cell->getColumn() === $this->quantityColumn) {
                $cell->setValueExplicit(
                    intval($value) * $this->lastMultiplicity,
                    DataType::TYPE_NUMERIC
                );
            } else if ($cell->getColumn() === $this->priceColumn) {
                $price = Str::replaceFirst(',', '.', $value) / $this->lastMultiplicity;
                $cell->setValueExplicit($price, DataType::TYPE_NUMERIC);
            } else if ($cell->getColumn() === $this->amountColumn) {
                $cell->setValueExplicit(Str::replaceFirst(',', '.', $value), DataType::TYPE_NUMERIC
                );
            } else {
                $cell->setValueExplicit($value, DataType::TYPE_STRING);
            }
        }
        return true;
    }
}
