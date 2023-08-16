<!DOCTYPE html>
<html lang="ru">
    <head>
        <style>
            @page {
                margin: 10px !important;
                padding: 0 !important;
            }
        </style>
    </head>
    <body>
        @foreach($pickUps as $pickUp)
        <table style="border-spacing: 0px; page-break-after: {{ $loop->last ? 'avoid' : 'always' }}">
            <tbody>
                <tr>
                    <td style="font-size: 10px; border-bottom: solid" colspan="2"><b>{{ $pickUp->good->name->NAME
                    }}</b></td>
                </tr>
                <tr>
                    <td style="font-size: 8px;">Покупатель:</td>
                    <td style="font-size: 10px;">{{ $pickUp->invoiceLine->invoice->buyer->SHORTNAME
                    }}</td>
                </tr>
                <tr>
                    <td style="font-size: 8px;">Номер счета:</td>
                    <td style="font-size: 10px;">{{ $pickUp->invoiceLine->invoice->NS }}</td>
                </tr>
                <tr>
                    <td style="font-size: 8px;">Количество:</td>
                    <td style="font-size: 14px;"><b>{{ $pickUp->QUANSKLADNEED -
                    $pickUp->QUANSKLAD }}</b></td>
                </tr>
                <tr>
                    <td style="font-size: 8px;">Подобрано:</td>
                    <td style="font-size: 8px;">{{ \Carbon\Carbon::now()->format('d.m.Y H:i')
                    }}</td>
                </tr>
                <tr>
                    <td style="font-size: 8px;">Сотрудник</td>
                    <td style="font-size: 10px;">{{ $employee->FULLNAME }}</td>
                </tr>
            </tbody>
        </table>
        </div>
        @endforeach
    </body>
</html>
