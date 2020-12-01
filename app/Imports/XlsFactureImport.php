<?php

namespace App\Imports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

HeadingRowFormatter::default('none');

class XlsFactureImport implements WithMapping, WithHeadingRow
{
    private $newRow = [
        'name' => ['наименование', 'название', 'имя', 'name'],
        'quantity' => ['количество', 'кол.', 'кол', 'кол-во', 'кол.-во', 'qnt', 'quan', 'quantity'],
        'price' => ['цена', 'цена товара', 'price'],
        'priceWithoutVat' => ['цена без ндс', 'price without vat'],
        'amount' => ['сумма', 'валютная сумма', 'amount'],
        'amountWithoutVat' => ['сумма с ндс'],
        'multiplicity' => ['кратность', 'Цена указана за ... шт.'],
        'country' => ['страна происхождения', 'страна', 'country'],
        'declaration' => ['код таможенной декларации', 'гтд', 'declaration'],
        'producer' => ['производитель', 'producer'],
        'case' => ['case', 'корпус'],
    ];

    public function map($row): array
    {
        $newRow = $this->newRow;
        array_walk($newRow, function (&$value) use ($row) {
            foreach ($row as $rowKey => $rowValue) {
                if (in_array(strtolower($rowKey), $value, true)) {
                    $value = $rowValue;
                    break;
                }
            }
        });
        $newRow = array_filter($newRow, function($v) {
            return !is_array($v);
        });
        if (!array_key_exists('quantity', $newRow)) {
            $newRow['quantity'] = 1;
        }
        if (array_key_exists('multiplicity', $newRow)) {
            $newRow['quantity'] = $newRow['quantity'] * $newRow['multiplicity'];
        }
        if (array_key_exists('amount', $newRow) && !array_key_exists('price', $newRow)) {
            $newRow['price'] = $newRow['amount'] / $newRow['quantity'];
        } elseif (!array_key_exists('amount', $newRow) && array_key_exists('price', $newRow)) {
            $newRow['amount'] = $newRow['price'] * $newRow['quantity'];
        } elseif (array_key_exists('amountWithoutVat', $newRow)) {
            $newRow['amount'] = $newRow['amountWithoutVat'] * ( 1 + VAT::get(Carbon::now()) / 100);
            $newRow['price'] = $newRow['amount'] / $newRow['quantity'];
        } elseif (array_key_exists('priceWithoutVat', $newRow)) {
            $newRow['price'] = $newRow['priceWithoutVat'] * ( 1 + VAT::get(Carbon::now()) / 100);
            $newRow['amount'] = $newRow['price'] * $newRow['quantity'];
        }
        return $newRow;
    }
}
