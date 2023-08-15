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
                    <td style="font-size: 10px;">Наименование:</td>
                    <td style="font-size: 10px; padding: 2px;">{{ $pickUp->good->name->NAME }}</td>
                </tr>
                <tr>
                    <td style="font-size: 10px;">Покупатель:</td>
                    <td style="font-size: 10px; padding: 2px;">{{ $pickUp->invoiceLine->invoice->buyer->SHORTNAME
                    }}</td>
                </tr>
                <tr>
                    <td style="font-size: 10px;">Номер счета:</td>
                    <td style="font-size: 14px; padding: 2px;"><b>{{ $pickUp->invoiceLine->invoice->NS }}</b></td>
                </tr>
                <tr>
                    <td style="font-size: 10px;">Количество:</td>
                    <td style="font-size: 20px; padding: 2px;"><b>{{ $pickUp->QUANSKLADNEED -
                    $pickUp->QUANSKLAD }}</b></td>
                </tr>
                <tr>
                    <td style="font-size: 10px;">Подобрано:</td>
                    <td style="font-size: 10px; padding: 2px;"><b>{{ \Carbon\Carbon::now()->format('d.m.Y H:i')
                    }}</b></td>
                </tr>
                <tr>
                    <td style="font-size: 10px;">Сотрудник</td>
                    <td style="font-size: 10px; padding: 2px;">{{ $employee->FULLNAME }}</td>
                </tr>
            </tbody>
        </table>
        </div>
        @endforeach
    </body>
</html>
