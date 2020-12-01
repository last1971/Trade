<?php


namespace App\Services;


use App\Currency;
use App\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class RateExchangeService
{
    /**
     * @param Carbon $date
     * @return Collection
     */
    public function loadFromCbr(Carbon $date): Collection
    {
        $response =
            simplexml_load_file(
                'http://www.cbr.ru/scripts/XML_daily.asp?date_req=' . $date->format('d/m/Y')
            );
        foreach ($response->Valute as $currencyRate) {
            $currency = Currency::query()->firstOrCreate(
                [
                    'CharCode' => $currencyRate->CharCode
                ],
                [
                    'NumCode' => $currencyRate->NumCode,
                    'Name' => $currencyRate->Name,
                ]
            );
            ExchangeRate::query()->firstOrCreate(
                [
                    'CharCode' => $currency->CharCode,
                    'date' => $date->format('Y-m-d')
                ],
                [
                    'value' => str_replace(',', '.', $currencyRate->Value) / $currencyRate->Nominal
                ]
            );
        }
        $currency = Currency::query()->firstOrCreate(
            [
                'CharCode' => 'RUB'
            ],
            [
                'NumCode' => '643',
                'Name' => 'Российский рубль',
            ]
        );
        ExchangeRate::query()->firstOrCreate(
            [
                'CharCode' => $currency->CharCode,
                'date' => $date->format('Y-m-d')
            ],
            [
                'value' => 1
            ]
        );
        return ExchangeRate::with('currency')->whereDate('date', $date->format('Y-m-d'))->get();
    }

    public function getOnDate(Carbon $date): Collection
    {
        $exchnageRates = ExchangeRate::with('currency')
            ->whereDate('date', $date->format('Y-m-d'))
            ->get();
        if ($exchnageRates->count() === 0) {
            $exchnageRates = $this->loadFromCbr($date);
        }
        return $exchnageRates;
    }
}
