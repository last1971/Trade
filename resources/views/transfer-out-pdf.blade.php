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
<table class="main">
    <tr>
        <td colspan="15">
            Второй экземпляр документов, просим подписать и отправить почтой по адресу: 634009, г. Томск, а/я 1361, ООО
            "ЭлкоПро"
        </td>
    </tr>
    <tr>
        <td class="right-border-double" colspan="2" style="vertical-align: text-top;">
            Универсальный<br>
            передаточный<br>
            документ<br>
            <br>
            Статус:&nbsp;
            <span style="border: 2px solid #000;padding: 1px 8px 1px 10px;">1</span>
            <br>
            <p>
                1 – счет-фактура и
                передаточный документ
                (акт)<br>
                <span class="nowrap">2 – передаточный</span>
                документ (акт)
            </p>
        </td>
        <td colspan="14">
            <table width="100%">
                <tbody>
                <tr>
                    <td width="30%">
                        <table width="100%">
                            <tbody>
                            <tr>
                                <td style="font-size: 11px; padding-left: 4px; vertical-align: top">
                                    <b>
                                        Счет-фактура № {{ $transferOut->NSF }} от
                                        {{ (new Date($transferOut->DATA))->format('j F Y г.') }}
                                    </b>
                                </td>
                                <td>(1)</td>
                            </tr>
                            <tr>
                                <td style="padding-left:4px">
                                    Исправление №
                                    ---
                                    от
                                    ---
                                </td>
                                <td>(1a)</td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                    <td>
                        <table width="100%">
                            <tbody>
                            <tr>
                                <td style="white-space: nowrap; text-align: right; font-size: 7px; color: #6c757d">
                                    Приложение N 1
                                    <br>
                                    к постановлению Правительства Российской Федерации
                                    <br>
                                    от 26.12.2011 №1137
                                    <br>
                                    (в ред. Постановления Правительства РФ от 19.08.2017 № 981)
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                </tbody>
            </table>
            <table width="100%">
                <tr>
                    <td><b>Продавец</b></td>
                    <td width="75%" class="bottom-border">
                        <b>{{ $transferOut->firm->FIRMNAME }}</b>
                    </td>
                    <td width="3%" class="centred">(2)</td>
                </tr>
                <tr>
                    <td>Адрес</td>
                    <td width="75%" class="bottom-border">
                        {{ $transferOut->firm->ADDRESS }}
                    </td>
                    <td width="3%" class="centred">(2а)</td>
                </tr>
                <tr>
                    <td>ИНН/КПП продавца</td>
                    <td width="75%" class="bottom-border">
                        {{ $transferOut->firm->getAttributes()['INN'] }}
                    </td>
                    <td width="3%" class="centred">(2б)</td>
                </tr>
                <tr>
                    <td>Грузоотправитель и его адрес:</td>
                    <td width="75%" class="bottom-border">
                        <b>{{ $transferOut->firm->FIRMNAME }}, </b>
                        {{ $transferOut->firm->ADDRESS }}
                    </td>
                    <td width="3%" class="centred">(3)</td>
                </tr>
                <tr>
                    <td>Грузополучатель и его адрес:</td>
                    <td width="75%" class="bottom-border">
                        <b>{{ $transferOut->buyer->advancedBuyer->consignee ?? $transferOut->buyer->FULLNAME }}, </b>
                        {{ $transferOut->buyer->advancedBuyer->consigneeAddress ?? $transferOut->buyer->ADDRESS }}
                    </td>
                    <td width="3%" class="centred">(4)</td>
                </tr>
                <tr>
                    <td>К платежно-расчетному документу</td>
                    @if ($cashFlows->isEmpty())
                        <td width="75%" class="bottom-border">-</td>
                    @else
                        <td width="75%" class="bottom-border">
                            @foreach($cashFlows as $cf)
                                № {{ $cf->NPP }}
                                от {{ (new Date($cf->DATA))->format('d.m.Y') }}
                                @if ($loop->remaining)
                                    <span>, </span>
                                @endif
                            @endforeach
                        </td>
                    @endif
                    <td width="3%" class="centred">(5)</td>
                </tr>
                <tr>
                    <td><b>Покупатель</b></td>
                    <td width="75%" class="bottom-border">
                        <b>{{ $transferOut->buyer->FULLNAME }}</b>
                    </td>
                    <td width="3%" class="centred">(6)</td>
                </tr>
                <tr>
                    <td>Адрес</td>
                    <td width="75%" class="bottom-border">
                        {{ $transferOut->buyer->ADDRESS }}
                    </td>
                    <td width="3%" class="centred">(6а)</td>
                </tr>
                <tr>
                    <td>ИНН/КПП покупателя</td>
                    <td width="75%" class="bottom-border">
                        {{ $transferOut->buyer->getAttributes()['INN'] }}
                    </td>
                    <td width="3%" class="centred">(6б)</td>
                </tr>
                <tr>
                    <td>Валюта: наименование, код</td>
                    <td width="75%" class="bottom-border">
                        Российский рубль, код - 643
                    </td>
                    <td width="3%" class="centred">(7)</td>
                </tr>
            </table>
            <table width="100%">
                <tr>
                    <td>Идентификатор государственного контракта, договора (соглашения) (при наличии)</td>
                    <td width="60%" class="bottom-border"></td>
                    <td width="3%" class="centred">(8)</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr class="heading">
        <td rowspan="2" class="top-border right-border left-border">№ п/п</td>
        <td rowspan="2" class="right-border-double top-border" width="5%">Код товара/работ, услуг</td>
        <td rowspan="2" class="top-border right-border" width="20%">
            Наименование товара (описание выполненных работ, оказанных услуг), имущественного права
        </td>
        <td rowspan="2" class="top-border right-border">Код вида товара</td>
        <td colspan="2" class="top-border right-border">Единица измерения</td>
        <td rowspan="2" class="top-border right-border">Количество (объем)</td>
        <td rowspan="2" class="top-border right-border">Цена,(тариф) за единицу измерения руб. коп.</td>
        <td rowspan="2" class="top-border right-border">Стоимость товаров (работ,услуг), имущественных прав без налога -
            всего
        </td>
        <td rowspan="2" class="top-border right-border">В том числе сумма акциза</td>
        <td rowspan="2" class="top-border right-border">Налого-вая ставка</td>
        <td rowspan="2" class="top-border right-border">Сумма налога, предъявляемая покупателю</td>
        <td rowspan="2" class="top-border right-border">Стоимость товаров (работ, услуг), имущественных прав с налогом -
            всего
        </td>
        <td colspan="2" class="top-border right-border">Страна происхождения товара</td>
        <td rowspan="2" class="top-border right-border">
            Регистрационный<br>номер<br>таможенной<br>декларации
        </td>
    </tr>
    <tr class="heading">
        <td class="top-border right-border">код</td>
        <td class="top-border right-border" width="5%">условное обозначение (националь- ное)</td>
        <td class="top-border right-border">Цифро-вой код</td>
        <td class="top-border right-border">краткое наименование</td>
    </tr>
    <thead>
    <tr>
        <th class="top-border right-border left-border bottom-border">А</th>
        <th class="right-border-double top-border bottom-border">Б</th>
        <th class="top-border right-border bottom-border">1</th>
        <th class="top-border right-border bottom-border">1а</th>
        <th class="top-border right-border bottom-border">2</th>
        <th class="top-border right-border bottom-border">2а</th>
        <th class="top-border right-border bottom-border">3</th>
        <th class="top-border right-border bottom-border">4</th>
        <th class="top-border right-border bottom-border">5</th>
        <th class="top-border right-border bottom-border">6</th>
        <th class="top-border right-border bottom-border">7</th>
        <th class="top-border right-border bottom-border">8</th>
        <th class="top-border right-border bottom-border">9</th>
        <th class="top-border right-border bottom-border">10</th>
        <th class="top-border right-border bottom-border">10а</th>
        <th class="top-border right-border bottom-border">11</th>
    </tr>
    </thead>
    @foreach($transferOutLines as $line)
        <tr>
            <td class="left-border right-border bottom-border centred">{{ $loop->iteration }}</td>
            <td class="right-border-double bottom-border centred">{{ $line->good->GOODSCODE }}</td>
            <td class="right-border bottom-border text-justify" style="padding: 2px">
                {{$line->name->NAME}}
                @if ($line->good->BODY)
                    / {{ $line->good->BODY }}
                @endif
                @if ($line->good->BODY)
                    / {{ $line->good->PRODUCER }}
                @endif
                <br/>
                <span style="font-size: 6px; font-style: italic">{{ $line->category->CATEGORY }}</span>
            </td>
            <td class="right-border bottom-border centred">-</td>
            <td class="right-border bottom-border centred">{{ $line->good->unitCode }}</td>
            <td class="right-border bottom-border centred">{{ $line->good->unitName }}</td>
            <td class="right-border bottom-border text-right">{{ $line->QUAN }}</td>
            <td class="right-border bottom-border text-right">
                {{ number_format($line->priceWithoutVat, 2, '.', ' ') }}
            </td>
            <td class="right-border bottom-border text-right">
                {{ number_format($line->amountWithoutVat, 2, '.', ' ') }}
            </td>
            <td class="right-border bottom-border centred">без акциза</td>
            <td class="right-border bottom-border centred">{{ VAT::get($transferOut->DATA) }}%</td>
            <td class="right-border bottom-border text-right">
                {{ number_format($line->SUMMAP - $line->amountWithoutVat, 2, '.', ' ') }}
            </td>
            <td class="right-border bottom-border text-right">{{ number_format($line->SUMMAP, 2, '.', ' ') }}</td>
            <td class="right-border bottom-border centred">
                {{ $line->countryNumCode ?? '-' }}
            </td>
            <td class="right-border bottom-border centred">
                {{ $line->countryNumCode ? $line->STRANA : '-' }}
            </td>
            <td class="right-border bottom-border centred">
                @if ($line->countryNumCode)
                    {{ \Illuminate\Support\Str::beforeLast($line->GTD, '/')}}<br/>
                    /{{ \Illuminate\Support\Str::afterLast($line->GTD, '/')}}
                @else
                    -
                @endif
            </td>
        </tr>
    @endforeach
    <tfoot style="page-break-inside: avoid;">
    <tr>
        <td colspan="2" class="left-border right-border-double bottom-border"></td>
        <td colspan="6" class="right-border bottom-border">
            <b>Всего к оплате</b>
        </td>
        <td class="right-border bottom-border text-right">
            {{ number_format($transferOutLines->sum('amountWithoutVat'), 2, '.', ' ') }}
        </td>
        <td colspan="2" class="right-border bottom-border centred">Х</td>
        <td class="right-border bottom-border text-right">
            {{ number_format($transferOutLines->sum('SUMMAP') - $transferOutLines->sum('amountWithoutVat'), 2, '.', ' ') }}
        </td>
        <td class="right-border bottom-border text-right">
            <b>{{ number_format($transferOutLines->sum('SUMMAP'), 2, '.', ' ') }}</b>
        </td>
    </tr>
    <tr>
        <td class="right-border-double" colspan="2" style="vertical-align: text-top;">
            Документ<br>составлен на<br/>
            <span style="border-bottom: 1px solid black">&nbsp;</span>
            {{ $count  }}
            <span style="border-bottom: 1px solid black">&nbsp;</span>
            листах
        </td>
        <td class="bottom-border-double" colspan="14">
            <table width="100%" style="border-spacing: 10px 1px;">
                <tr>
                    <td>Руководитель организации<br>или иное уполномоченное лицо</td>
                    <td class="bottom-border" width="12%"></td>
                    <td class="bottom-border centred">{{ $transferOut->employee->FULLNAME }}</td>
                    <td>Главный бухгалтер<br>или иное уполномоченное лицо</td>
                    <td class="bottom-border" width="12%"></td>
                    <td class="bottom-border centred">{{ $transferOut->employee->FULLNAME }}</td>
                </tr>
                <tr class="heading" style="font-size: 6px;">
                    <td></td>
                    <td>(подпись)</td>
                    <td>(ф.и.о.)</td>
                    <td></td>
                    <td>(подпись)</td>
                    <td>(ф.и.о.)</td>
                </tr>
                <tr class="heading" style="font-size: 6px;">
                    <td></td>
                    <td colspan="2">На основнии приказа № 1/П от 09.01.2020</td>
                    <td></td>
                    <td colspan="2">На основнии приказа № 1/П от 09.01.2020</td>
                </tr>
                <tr>
                    <td>Индивидуальный предприниматель<br>или иное уполномоченное лицо</td>
                    <td class="bottom-border" width="12%"></td>
                    <td class="bottom-border centred"></td>
                    <td class="bottom-border" colspan="3"></td>
                </tr>
                <tr class="heading" style="font-size: 6px;">
                    <td></td>
                    <td>(подпись)</td>
                    <td>(ф.и.о.)</td>
                    <td colspan="3">
                        (реквизиты свидетельства о государственной регистрации индивидуального предпринимателя)
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    </tfoot>
</table>
<div style="page-break-inside: avoid;">
    <table width="100%" class="main">
        <tr>
            <td>Основание передачи (сдачи)/получения приемки</td>
            <td width="75%" class="bottom-border">
                Счет № {{ $transferOut->invoice->NS }}
                от {{ (new Date($transferOut->invoice->DATA))->format('d.m.Y') }}
                @if ($transferOut->invoice->NZ)
                    , GPC-00000{{ $transferOut->invoice->NZ }}
                @endif
            </td>
            <td width="1%">(8)</td>
        </tr>
        <tr class="heading" style="font-size: 6px;">
            <td></td>
            <td>(договор; доверенность и др.)</td>
            <td></td>
        </tr>
    </table>
    <table width="100%" class="main">
        <tr>
            <td>Данные о транспортировке и грузе</td>
            <td width="80%" class="bottom-border">
            <td width="1%">(9)</td>
        </tr>
        <tr class="heading" style="font-size: 6px;">
            <td></td>
            <td>
                (транспортная накладная, поручение экспедитору, экспедиторская / складская расписка и др. / масса нетто/
                брутто груза, если не приведены ссылки на транспортные документы, содержащие эти сведения)
            </td>
            <td></td>
        </tr>
    </table>
    <table class="main" width="100%" style="border-spacing: 10px 0px;">
        <tr>
            <td colspan="3">
                Товар (груз) передал/услуги, результаты работ, права сдал
            </td>
            <td class="right-border" width="3%"></td>
            <td colspan="3">
                Товар (груз) получил/услуги, результаты работ, права принял
            </td>
            <td width="3%"></td>
        </tr>
        <tr>
            <td class="centred bottom-border">{{ $transferOut->employee->employeePosition->NAME }}</td>
            <td class="bottom-border"></td>
            <td class="centred bottom-border">{{ $transferOut->employee->FULLNAME }}</td>
            <td class="right-border centred">(10)</td>
            <td class="bottom-border"></td>
            <td class="bottom-border"></td>
            <td class="bottom-border"></td>
            <td class="centred">(15)</td>
        </tr>
        <tr class="heading" style="font-size: 6px;">
            <td class="centred">(должность)</td>
            <td class="centred">(подпись)</td>
            <td class="centred">(ф.и.о.)</td>
            <td class="right-border"></td>
            <td class="centred">(должность)</td>
            <td class="centred">(подпись)</td>
            <td class="centred">(ф.и.о.)</td>
            <td></td>
        </tr>
        <tr>
            <td>Дата отгрузки, передачи (сдачи)</td>
            <td colspan="2" class="bottom-border">{{ (new Date($transferOut->DATA))->format('j F Y г.') }}</td>
            <td class="right-border centred">(11)</td>
            <td>Дата получения (приемки)</td>
            <td colspan="2" class="bottom-border"> "___" _______________ 20___ г.</td>
            <td class="centred">(16)</td>
        </tr>
        <tr>
            <td colspan="2">
                Иные сведения об отгрузке, передаче
            </td>
            <td colspan="2" class="right-border"></td>
            <td colspan="2">
                Иные сведения о получении, приемке
            </td>
            <td></td>
        </tr>
        <tr>
            <td colspan="3" class="bottom-border"></td>
            <td class="right-border centred">(12)</td>
            <td colspan="3" class="bottom-border"></td>
            <td class="centred">(17)</td>
        </tr>
        <tr class="heading" style="font-size: 6px;">
            <td colspan="3">(ссылки на неотъемлемые приложения, сопутствующие документы, иные документы и т.п.)</td>
            <td class="right-border"></td>
            <td colspan="3">
                (информация о наличии/отсутствии претензии; ссылки на неотъемлемые приложения, и другие документы и
                т.п.)
            </td>
            <td></td>
        </tr>
        <tr>
            <td colspan="4" class="right-border">
                Ответственный за правильность оформления факта хозяйственной жизни
            </td>
            <td colspan="4">Ответственный за правильность оформления факта хозяйственной жизни</td>
        </tr>
        <tr>
            <td class="centred bottom-border">{{ $transferOut->employee->employeePosition->NAME }}</td>
            <td class="bottom-border"></td>
            <td class="centred bottom-border">{{ $transferOut->employee->FULLNAME }}</td>
            <td class="right-border centred">(13)</td>
            <td class="bottom-border"></td>
            <td class="bottom-border"></td>
            <td class="bottom-border"></td>
            <td class="centred">(18)</td>
        </tr>
        <tr class="heading" style="font-size: 6px;">
            <td class="centred">(должность)</td>
            <td class="centred">(подпись)</td>
            <td class="centred">(ф.и.о.)</td>
            <td class="right-border"></td>
            <td class="centred">(должность)</td>
            <td class="centred">(подпись)</td>
            <td class="centred">(ф.и.о.)</td>
            <td></td>
        </tr>
        <tr class="heading" style="font-size: 6px;">
            <td class="centred"></td>
            <td class="centred" colspan="2">На основнии приказа № 1/П от 09.01.2020</td>
            <td class="right-border"></td>
            <td class="centred"></td>
            <td class="centred" colspan="2"></td>
            <td></td>
        </tr>
        <tr>
            <td colspan="4" class="right-border">
                Наименование экономического субъекта – составителя документа (в т.ч. комиссионера / агента)
            </td>
            <td colspan="4">Наименование экономического субъекта – составителя документа</td>
        </tr>
        <tr>
            <td colspan="3" class="bottom-border">
                {{ $transferOut->firm->FIRMNAME }} ИНН/КПП {{ $transferOut->firm->getAttributes()['INN']  }}
            </td>
            <td class="right-border centred">(14)</td>
            <td colspan="3" class="bottom-border">
                {{ $transferOut->buyer->FULLNAME }} ИНН/КПП {{ $transferOut->buyer->getAttributes()['INN']  }}
            </td>
            <td class="centred">(19)</td>
        </tr>
        <tr class="heading" style="font-size: 6px;">
            <td colspan="3">(может не заполняться при проставлении печати в М.П., может быть указан ИНН / КПП)</td>
            <td class="right-border"></td>
            <td colspan="3">
                (может не заполняться при проставлении печати в М.П., может быть указан ИНН / КПП)
            </td>
            <td></td>
        </tr>
        <tr>
            <td class="centred">М.П.</td>
            <td></td>
            <td colspan="2" class="right-border"></td>
            <td class="centred">М.П.</td>
            <td></td>
            <td colspan="2"></td>
        </tr>
    </table>
</div>
</body>
</html>
