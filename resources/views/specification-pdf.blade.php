<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style type="text/css">
        * {
            font-family: dompdf_arial;
            font-size: 11px;
            line-height: 13px;
        }

        body {
            margin: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
        }

        .header-table td {
            vertical-align: top;
            padding: 3px 0;
        }

        .logo-cell {
            width: 180px;
        }

        .logo-cell img {
            max-width: 160px;
        }

        .spec-number {
            text-align: right;
            font-size: 11px;
        }

        .intro {
            text-align: justify;
            margin: 10px 0;
            font-size: 10px;
            line-height: 14px;
        }

        .list {
            margin: 10px 0;
        }

        .list th, .list td {
            border: 1px solid #000;
            padding: 3px 4px;
            text-align: center;
            font-size: 10px;
        }

        .list th {
            background: #f5f5f5;
            font-weight: bold;
        }

        .list td.left {
            text-align: left;
        }

        .list td.right {
            text-align: right;
        }

        .totals {
            margin: 5px 0;
        }

        .totals td {
            padding: 2px 4px;
            font-size: 10px;
        }

        .totals .label {
            text-align: right;
            border: 1px solid #000;
        }

        .totals .value {
            text-align: right;
            border: 1px solid #000;
            font-weight: bold;
        }

        .total-words {
            margin: 10px 0;
            font-weight: bold;
            font-size: 10px;
        }

        .conditions {
            margin: 10px 0;
            font-size: 9px;
            line-height: 12px;
        }

        .conditions p {
            margin: 2px 0;
        }

        .signatures {
            margin-top: 15px;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
        }

        .sign-table {
            margin-top: 10px;
        }

        .sign-table td {
            width: 50%;
            vertical-align: top;
            padding: 5px 15px;
            font-size: 10px;
        }

        .sign-line {
            border-bottom: 1px solid #000;
            margin-top: 30px;
            padding-bottom: 3px;
        }

        .sign-label {
            font-size: 9px;
            margin-top: 3px;
        }

        .stamp {
            position: absolute;
            left: 100px;
            margin-top: -140px;
        }

        .stamp img {
            max-width: 230px;
            opacity: 0.7;
        }

        .footer-block {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
<table class="header-table">
    <tr>
        <td class="logo-cell">
            <img src="{{ storage_path('fonts/logo.png') }}">
        </td>
        <td class="spec-number">
            Спецификация № {{ $invoice->NS }} от {{ (new Date($invoice->DATA))->format('j F Y') }}г.<br>
            к договору поставки № 2 от «01» февраля 2017 г.
        </td>
    </tr>
</table>

<div class="intro">
    ООО «ВИЗМ», именуемое в дальнейшем Покупатель, в лице Генерального директора, Баранникова Геннадия Владимировича, действующего на основании
    Устава, и ООО «ЭлкоПро», именуемое в дальнейшем «Поставщик», в лице Коммерческого директора, Ластовка В.В., действующего на основании
    Доверенности № 3 от 01.01.2021г., составили настоящую Спецификацию к Договору поставки № 2 от «01 » февраля 2017 г. (далее по тексту – «Договор») о
    нижеследующем:
</div>

<table class="list">
    <thead>
    <tr>
        <th width="4%">№ п/п</th>
        <th width="38%">Наименование товара</th>
        <th width="6%">Ед. изм. товара</th>
        <th width="6%">Кол-во товара</th>
        <th width="12%">Цена без НДС (руб.) за ед.</th>
        <th width="10%">НДС (руб.) за ед.</th>
        <th width="12%">Общая Стоимость (руб.)</th>
        <th width="12%">Срок поставки</th>
    </tr>
    </thead>
    <tbody>
    @foreach($lines as $line)
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td class="left">
                {{ $line->name->NAME }}
                @if ($line->good->BODY && ($body ?? false))
                    @if ($divider ?? false) / @endif
                    {{ $line->good->BODY }}
                @endif
                @if ($line->good->PRODUCER && ($producer ?? false))
                    @if ($divider ?? false) / @endif
                    {{ $line->good->PRODUCER }}
                @endif
            </td>
            <td>{{ empty($line->good->UNIT_I) ? 'шт.' : $line->good->UNIT_I }}</td>
            <td>{{ $line->QUAN }}</td>
            <td class="right">{{ number_format($line->SUMMAP / (100 + VAT::get($invoice->DATA)) * 100 / $line->QUAN, 2, ',', ' ') }}</td>
            <td class="right">{{ number_format($line->SUMMAP / (100 + VAT::get($invoice->DATA)) * VAT::get($invoice->DATA) / $line->QUAN, 2, ',', ' ') }}</td>
            <td class="right">{{ number_format($line->SUMMAP, 2, ',', ' ') }}</td>
            <td>{{ $line->PRIM ?: '9 дней' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="totals">
    <tr>
        <td width="70%"></td>
        <td class="label" width="15%">Сумма без НДС</td>
        <td class="value" width="15%">{{ number_format($invoice->invoiceLines->sum('SUMMAP') / (100 + VAT::get($invoice->DATA)) * 100, 2, ',', ' ') }} р.</td>
    </tr>
    <tr>
        <td></td>
        <td class="label">НДС</td>
        <td class="value">{{ number_format($invoice->invoiceLines->sum('SUMMAP') / (100 + VAT::get($invoice->DATA)) * VAT::get($invoice->DATA), 2, ',', ' ') }} р.</td>
    </tr>
    <tr>
        <td></td>
        <td class="label">Итого с НДС</td>
        <td class="value">{{ number_format($invoice->invoiceLines->sum('SUMMAP'), 2, ',', ' ') }} р.</td>
    </tr>
</table>

<div class="footer-block">
    <div class="total-words">
        Всего к оплате:
        {{
            Str::ucfirst(
                (new \NumberToWords\NumberToWords())
                    ->getCurrencyTransformer('ru')
                    ->toWords($invoice->invoiceLines->sum('SUMMAP') * 100, 'RUB'),
                 MB_CASE_TITLE_SIMPLE
            )
        }}
    </div>

    <div class="conditions">
        <p><strong>1. Ассортимент, количество, цена и общая стоимость Товара</strong></p>
        <p><strong>2. Срок оплаты Товара:</strong> Оплата производится в 5 дневный срок после поступления товара на склад покупателя</p>
        <p><strong>3. Порядок оплаты Товара:</strong> безналичный</p>
        <p><strong>4</strong> Настоящее приложение составлено в двух идентичных экземплярах, имеющих равную юридическую силу, по одному для каждой из Сторон.</p>
        <p><strong>5</strong> Настоящее приложение является неотъемлемой частью Договора.</p>
        <p><strong>6</strong> Настоящее приложение применяется с момента подписания Сторонами.</p>
        <p>&nbsp;</p>
    </div>

    <div class="signatures">ПОДПИСИ СТОРОН:</div>

    <table class="sign-table">
        <tr>
            <td style="position: relative;">
                <div>От Поставщика</div>
                <div class="sign-line">Ластовка В.В. ___________________</div>
                <div class="sign-label">М.П.</div>
                @if ($withStamp ?? false)
                    <div class="stamp">
                        <img src="{{ storage_path('fonts/lastovka.png') }}">
                    </div>
                @endif
            </td>
            <td>
                <div>От Покупателя</div>
                <div class="sign-line">Баранников Г.В. ___________________</div>
                <div class="sign-label">М.П.</div>
            </td>
        </tr>
    </table>
</div>

<script type="text/php">
    if (isset($pdf)) {
        $text = "Лист {PAGE_NUM} / Листов {PAGE_COUNT}";
        $size = 10;
        $font = $fontMetrics->getFont("Verdana");
        $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
        $x = ($pdf->get_width() - $width) / 2;
        $y = $pdf->get_height() - 30;
        $pdf->page_text($x, $y, $text, $font, $size);
    }
</script>
</body>
</html>
