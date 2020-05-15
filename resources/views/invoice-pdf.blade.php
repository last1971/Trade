<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style type="text/css">
        * {
            font-family: dompdf_arial;
            font-size: 14px;
            line-height: 14px;
        }

        table {
            margin: 0 0 15px 0;
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }

        table td {
            padding: 5px;
        }

        table th {
            padding: 5px;
            font-weight: bold;
        }

        .header {
            margin: 0 0 0 0;
            padding: 0 0 15px 0;
            font-size: 10px;
            line-height: 12px;
            text-align: justify;
        }

        /* Реквизиты банка */
        .details td {
            padding: 3px 2px;
            border: 1px solid #000000;
            font-size: 12px;
            line-height: 12px;
            vertical-align: top;
        }

        h1 {
            margin: 0 0 10px 0;
            padding: 10px 0 10px 0;
            border-bottom: 2px solid #000;
            font-weight: bold;
            font-size: 20px;
        }

        /* Поставщик/Покупатель */
        .contract th {
            padding: 3px 0;
            vertical-align: top;
            text-align: left;
            font-size: 13px;
            line-height: 15px;
        }

        .contract td {
            padding: 3px 0;
        }

        /* Наименование товара, работ, услуг */
        .list thead, .list tbody {
            border: 2px solid #000;
        }

        .list thead th {
            padding: 4px 0;
            border: 1px solid #000;
            vertical-align: middle;
            text-align: center;
        }

        .list tbody td {
            padding: 0 2px;
            border: 1px solid #000;
            vertical-align: middle;
            font-size: 11px;
            line-height: 13px;
        }

        .list tfoot th {
            padding: 3px 2px;
            border: none;
            font-size: 12px;
        }

        /* Сумма */
        .total {
            margin: 0 0 20px 0;
            padding: 0 0 10px 0;
            border-bottom: 2px solid #000;
        }

        .total p {
            margin: 0;
            padding: 0;
        }

        /* Руководитель, бухгалтер */
        .sign {
            position: relative;
        }

        .sign table {
            width: 72%;
        }

        .sign th {
            padding: 40px 0 0 0;
            text-align: left;
        }

        .sign td {
            padding: 40px 0 0 0;
            border-bottom: 1px solid #000;
            text-align: right;
            font-size: 12px;
        }

        .printing {
            position: absolute;
            left: 130px;
            top: -20px;
            opacity: 0.7;
            width: 27%;
        }

        .info {
            text-align: center;
            font-size: 11px;
        }

        .logo {
            position: absolute;
            top: -25px;
        }
    </style>
    <title></title>
</head>
<body>
<table>
    <tr>
        <td>
            <img class="logo" src="{{ storage_path('fonts/logo.png') }}">
        </td>
        <td class="info">
            ООО “ЭлкоПро” Комплексные поставки ЭЛектронных КОмпонентов
            634009 г. Томск, переулок 1905 года, дом 18,
            тел/факс: (3822) 511-225
            E-mail: opp@elcopro.ru http://www.elcopro.ru
        </td>
    </tr>
</table>
<table class="details">
    <tbody>
    @if ($newAccount)
        <tr>
            <td colspan="4" align="center">
                <h style="color: red">Внимaние! Новые реквизиты</h>
            </td>
        </tr>
    @endif
    <tr>
        <td colspan="2" style="border-bottom: none;">
            {{ $invoice->firmHistory ? $invoice->firmHistory->BANK : $invoice->firm->BANK }}
        </td>
        <td>БИК</td>
        <td style="border-bottom: none;">
            {{ $invoice->firmHistory ? $invoice->firmHistory->BIK : $invoice->firm->BIK }}
        </td>
    </tr>
    <tr>
        <td colspan="2" style="border-top: none; font-size: 10px;">Банк получателя</td>
        <td>Сч. №</td>
        <td style="border-top: none;">
            {{ $invoice->firmHistory ? $invoice->firmHistory->RS : $invoice->firm->RS }}
        </td>
    </tr>
    <tr>
        <td width="25%">ИНН {{ $invoice->firm->Inn }}</td>
        <td width="30%">КПП {{ $invoice->firm->Kpp }}</td>
        <td width="10%" rowspan="3">Сч. №</td>
        <td width="35%" rowspan="3">
            {{ $invoice->firmHistory ? $invoice->firmHistory->KS : $invoice->firm->KS }}
        </td>
    </tr>
    <tr>
        <td colspan="2" style="border-bottom: none;">{{ $invoice->firm->FIRMNAME }}</td>
    </tr>
    <tr>
        <td colspan="2" style="border-top: none; font-size: 10px;">Получатель</td>
    </tr>
    </tbody>
</table>

<h1>Счет на оплату № {{ $invoice->NS }} от
    {{ (new Date($invoice->DATA))->format('j F Y г.') }}
</h1>

<table class="contract">
    <tbody>
    <tr>
        <td width="15%">Поставщик:</td>
        <th width="85%">
            {{ $invoice->firm->FIRMNAME }},
            ИНН {{ $invoice->firm->Inn }},
            @if ($invoice->firm->Kpp)
                КПП {{ $invoice->firm->Kpp }},
            @endif
            {{ $invoice->firm->FACTADDRESS }}
        </th>
    </tr>
    <tr>
        <td>Покупатель:</td>
        <th>
            {{ $invoice->buyer->FULLNAME }},
            ИНН {{ $invoice->buyer->Inn }},
            @if ($invoice->buyer->Kpp)
                КПП {{ $invoice->buyer->Kpp }},
            @endif
            {{ $invoice->buyer->ADDRESS }}
        </th>
    </tr>
    </tbody>
</table>
<table class="list">
    <thead>
    <tr>
        <th width="4%">№</th>
        <th width="50%">Наименование товара, работ, услуг</th>
        <th width="8%">Коли-<br>чество</th>
        <th width="5%">Ед.<br>изм.</th>
        <th width="11%">Цена {{ $withVAT ? 'с' : 'без' }} НДС (руб.)</th>
        <th width="11%">Сумма {{ $withVAT ? 'с' : 'без' }} НДС (руб.)</th>
        <th width="11%">Срок поставки</th>
    </tr>
    </thead>
    <tbody>
    @foreach($lines as $line)
        <tr>
            <td align="center">{{ $loop->iteration }}</td>
            <td align="left">
                {{$line->name->NAME}}
                @if ($line->good->BODY)
                    / {{ $line->good->BODY }}
                @endif
                @if ($line->good->BODY)
                    / {{ $line->good->PRODUCER }}
                @endif
                <br/>
                <span style="font-size: 10px; font-style: italic">{{ $line->category->CATEGORY }}</span>
            </td>
            <td align="right">
                {{$line->QUAN}}
            </td>
            <td align="center">
                {{empty($line->good->UNIT_I) ? 'шт.' : $line->good->UNIT_I}}
            </td>
            <td align="right">
                {{number_format(
                    $withVAT ? $line->PRICE : $line->SUMMAP / (100 + VAT::get($invoice->DATA)) * 100 / $line->QUAN, 2
                )}}
            </td>
            <td align="right">
                {{number_format($withVAT ? $line->SUMMAP : $line->SUMMAP / (100 + VAT::get($invoice->DATA)) * 100, 2)}}
            </td>
            <td align="left">
                {{$line->PRIM}}
            </td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    @if(!$withVAT)
        <tr>
            <th colspan="5" align="right">Итого:</th>
            <th align="right">
                {{ number_format($invoice->invoiceLines->sum('SUMMAP') / (100 + VAT::get($invoice->DATA)) * 100, 2) }}
            </th>
            <th align="left">руб.</th>
        </tr>
    @endif
    <tr>
        <th colspan="5" align="right">Итого C НДС:</th>
        <th align="right">{{ number_format($invoice->invoiceLines->sum('SUMMAP'), 2) }}</th>
        <th align="left">руб.</th>
    </tr>
    <tr>
        <th colspan="5" align="right">В том числе НДС:</th>
        <th align="right">
            {{number_format(
                $invoice->invoiceLines->sum('SUMMAP') / (100 + VAT::get($invoice->DATA)) * VAT::get($invoice->DATA), 2
            )}}
        </th>
        <th align="left">руб.</th>
    </tr>
    </tfoot>
</table>
<div class="total" style="page-break-inside: avoid">
    <p>
        Всего наименований {{ $invoice->invoiceLines->count() }}, на сумму
        {{ number_format($invoice->invoiceLines->sum('SUMMAP'), 2) }} руб.
    </p>
    <p>
        <strong>
            {{
                Str::ucfirst(
                    (new \NumberToWords\NumberToWords())
                        ->getCurrencyTransformer('ru')
                        ->toWords($invoice->invoiceLines->sum('SUMMAP') * 100, 'RUB'),
                     MB_CASE_TITLE_SIMPLE
                )
            }}
        </strong>
    </p>
</div>
<div class="sign" style="page-break-inside: avoid">
    @if ($withStamp)
        <img class="printing" src="{{ storage_path('fonts/stamp.png') }}">
    @endif
    <table>
        <tbody>
        <tr>
            <th width="30%">Руководитель</th>
            <td width="70%">{{ $invoice->firm->DIRECTOR }}</td>
        </tr>
        <tr>
            <th>Бухгалтер</th>
            <td>{{ $invoice->firm->BUH }}</td>
        </tr>
        </tbody>
    </table>
</div>
<p class="header" style="page-break-inside: avoid">
    Данный счет действителен только в течении 2 банковских дней с момента его выставления. Перед оплатой счета
    обязательно проверьте все предложенные замены и соответствие выписанных позиций вашей заявке. Указанный в счете
    товар находится в резерве в течение 2 банковских дней. Резерв сохраняется при условии поступления денег на р. счет
    или уведомления об оплате. При несоблюдении этого условия резерв снимается, и товар отпускается из свободного
    наличия на складе по ценам, действующим на день поступления денег. Сразу после оплаты счета сообщите номер и дату
    платежного поручения, это значительно ускорит обработку вашего заказа. По платежному поручению товар не выдается.
    Сроки поставки указаны в банковских днях. Сроком поставки считается срок с момента поступления денежных средств на
    расчетный счет поставщика. В период действия счета, при изменении курса доллара США к рублю более, чем на 2%, цены
    подлежат пересмотрению, а счет должен быть перевыставлен с новыми условиями поставки.
</p>
<script type="text/php">
    if (isset($pdf)) {
        $text = "Страница {PAGE_NUM} из {PAGE_COUNT}";
        $size = 10;
        $font = $fontMetrics->getFont("Verdana");
        $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
        $x = ($pdf->get_width() - $width) / 2;
        $y = $pdf->get_height() - 35;
        $pdf->page_text($x, $y, $text, $font, $size);
    }


</script>
</body>
</html>
