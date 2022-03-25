<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <style>
        .main {
            width: 100%;
            font-size: 8px;
            border-spacing: 0px;
            font-family: dompdf_arial;
        }

        .heading {
            vertical-align: text-top;
            text-align: center;
        }

        .centred {
            text-align: center;
        }

        .text-justify {
            text-align: justify;
        }

        .text-right {
            text-align: right;
        }

        .right-border-double {
            border-right: 2px solid #000000;
        }

        .right-border {
            border-right: 1px solid rgba(0, 0, 0, 0.3);
        }

        .left-border {
            border-left: 1px solid rgba(0, 0, 0, 0.3);
        }

        .top-border {
            border-top: 1px solid rgba(0, 0, 0, 0.3);
        }

        .bottom-border {
            border-bottom: 1px solid rgba(0, 0, 0, 0.3);
        }

        .bottom-border-double {
            border-bottom: 2px solid #000000;;
        }

        td {
            padding: 0px;
            padding-left: 2px;
            padding-right: 2px;
        }
    </style>
</head>
<body>
@foreach($transferOuts as $transferOut)
    <div style="page-break-inside: avoid">
    <table class="main">
        <tr>
            <td class="centred left-border top-border" style="font-size: 12px;">
                Грузоотправитель: {{ $transferOut->firm->FIRMNAME }}, ИНН {{ $transferOut->firm->INN }}
            </td>
            <td class="top-border"></td>
            <td class="centred top-border right-border left-border">Коды</td>
        </tr>
        <tr>
            <td class="centred left-border" style="font-size: 12px;">
                {{ $transferOut->firm->ADDRESS }}, Тел. +7-3822-511225
            </td>
            <td class="text-right">ИНН</td>
            <td class="centred right-border left-border top-border">{{ $transferOut->firm->INN }}</td>
        </tr>
        <tr>
            <td class="centred left-border" style="font-size: 12px;">
                Сайт: www.elcopro.ru, E-mail: info@elcopro.ru
            </td>
            <td></td>
            <td class="right-border left-border top-border"></td>
        </tr>
        <tr>
            <td class="centred left-border top-border" style="font-size: 12px;">
                Грузополучатель: {{ $transferOut->buyer->advancedBuyer->consignee ?? $transferOut->buyer->FULLNAME }}
            </td>
            <td class="top-border text-right">ИНН</td>
            <td class="centred top-border right-border left-border">{{ $transferOut->buyer->INN }}</td>
        </tr>
        <tr>
            <td class="centred left-border" style="font-size: 12px;">
                Адрес доставки: {{ $transferOut->buyer->advancedBuyer->consigneeAddress ?? $transferOut->buyer->ADDRESS }}
            </td>
            <td></td>
            <td class="right-border left-border top-border"></td>
        </tr>
        <tr>
            <td style="font-size: 10px; text-align: left" class="centred top-border">Основание</td>
            <td class="text-right top-border">Счет</td>
            <td class="top-border right-border left-border">{{ $transferOut->invoice->NS }}</td>
        </tr>
        <tr>
            <td></td>
            <td class="text-right">Дата Счета</td>
            <td class="top-border right-border left-border bottom-border">
                {{ (new Date($transferOut->invoice->DATA))->format('d.m.Y') }}
            </td>
        </tr>
    </table>
    <table class="main">
        <tr>
            <td></td>
            <td class="left-border top-border centred">
                Номер документа
            </td>
            <td class="left-border right-border top-border centred">
                Дата составления
            </td>
            <td width="20%"></td>
        </tr>
        <tr>
            <td class="text-right" style="font-size: 12px;">ТОВАРНАЯ НАКЛАДНАЯ</td>
            <td class="left-border top-border bottom-border centred" style="font-size: 12px;">{{ $transferOut->NSF }}</td>
            <td class="left-border right-border top-border bottom-border centred" style="font-size: 12px;">
                {{ (new Date($transferOut->DATA))->format('d.m.Y') }}
            </td>
            <td width="20%"></td>
        </tr>
    </table>
    <br/>
    <table class="main">
        <tr class="heading">
            <td class="top-border right-border left-border">№ п/п</td>
            <td class="top-border right-border" style="width: 60%">Наименование товара</td>
            <td class="top-border right-border">Единица измерения</td>
            <td class="top-border right-border">Количество</td>
            <td class="top-border right-border">Цена, руб. коп.</td>
            <td class="top-border right-border">Стоимость товаров без налога </td>
            <td class="top-border right-border">Стоимость товаров с налогом</td>
        </tr>
        <thead>
        <tr>
            <th class="top-border right-border left-border bottom-border">1</th>
            <th class="top-border right-border bottom-border">2</th>
            <th class="top-border right-border bottom-border">3</th>
            <th class="top-border right-border bottom-border">4</th>
            <th class="top-border right-border bottom-border">5</th>
            <th class="top-border right-border bottom-border">6</th>
            <th class="top-border right-border bottom-border">7</th>
        </tr>
        </thead>
        @foreach($transferOut->transferOutLines as $line)
        <tr>
            <td class="left-border right-border bottom-border centred">{{ $loop->iteration }}</td>
            <td class="right-border bottom-border text-justify" style="padding: 2px">
                {{$line->name->NAME}}
            </td>
            <td class="right-border bottom-border centred">{{ $line->good->unitName }}</td>
            <td class="right-border bottom-border text-right">{{ $line->QUAN }}</td>
            <td class="right-border bottom-border text-right">
                {{ number_format($line->priceWithoutVat, 2, '.', ' ') }}
            </td>
            <td class="right-border bottom-border text-right">
                {{ number_format($line->amountWithoutVat, 2, '.', ' ') }}
            </td>
            <td class="right-border bottom-border text-right">{{ number_format($line->SUMMAP, 2, '.', ' ') }}</td>
        </tr>
        @endforeach
        <tr>
            <td class="left-border bottom-border"></td>
            <td class="bottom-border" colspan="4"><b>Всего</b></td>
            <td class="bottom-border left-border text-right">
                <b>{{ number_format($transferOut->transferOutLines->sum('amountWithoutVat'), 2, '.', ' ') }}</b>
            </td>
            <td class="bottom-border left-border right-border text-right">
                <b>{{ number_format($transferOut->transferOutLines->sum('SUMMAP'), 2, '.', ' ') }}</b>
            </td>
        </tr>
    </table>
    <br/>
    <table class="main" style="font-size: 10px;">
        <tr>
            <td colspan="4">Всего отпущено на сумму</td>
            <td colspan="4" class="left-border"></td>
        </tr>
        <tr>
            <td colspan="4">
                {{
                    Str::ucfirst(
                        (new \NumberToWords\NumberToWords())
                            ->getCurrencyTransformer('ru')
                            ->toWords($transferOut->transferOutLines->sum('SUMMAP') * 100, 'RUB'),
                        MB_CASE_TITLE_SIMPLE
                    )
                }}
            </td>
            <td colspan="4" class="left-border"></td>
        </tr>
        <tr>
            <td colspan="4">.</td>
            <td colspan="4" class="left-border"></td>
        </tr>
        <tr>
            <td>Отпуск груза произвел:</td>
            <td colspan="3"></td>
            <td class="left-border"></td>
            <td colspan="3">Груз принял:</td>
        </tr>
        <tr>
            <td colspan="4">.</td>
            <td colspan="4" class="left-border"></td>
        </tr>
        <tr>
            <td class="centred bottom-border">{{ $transferOut->employee->employeePosition->NAME }}</td>
            <td class="centred bottom-border"></td>
            <td class="centred bottom-border">{{ $transferOut->employee->FULLNAME }}</td>
            <td></td>
            <td class="left-border"></td>
            <td class="bottom-border"></td>
            <td class="bottom-border"></td>
            <td class="bottom-border"></td>
        </tr>
        <tr class="heading" style="font-size: 6px;">
            <td class="centred" style="width: 15%">(должность)</td>
            <td class="centred" style="width: 15%">(подпись)</td>
            <td class="centred" style="width: 15%">(ф.и.о.)</td>
            <td></td>
            <td class="left-border"></td>
            <td class="centred" style="width: 15%">(должность)</td>
            <td class="centred" style="width: 15%">(подпись)</td>
            <td class="centred" style="width: 15%">(ф.и.о.)</td>
        </tr>
        <tr>
            <td>Дата отгрузки</td>
            <td colspan="2">{{ (new Date($transferOut->DATA))->format('j F Y г.') }}</td>
            <td></td>
            <td class="left-border"></td>
            <td>Дата получения</td>
            <td colspan="2"> "___" _______________ 20___ г.</td>
        </tr>
    </table>
    </div>
    <br/>
    <br/>
@endforeach
</body>
