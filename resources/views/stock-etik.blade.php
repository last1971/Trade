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
        <div style="page-break-after: {{ $loop->last ? 'avoid' : 'always' }}">
            <p style="font-size: 11px; text-align: center;">{{ $invoice->firm->FIRMNAME }}</p>
        <table style="border-spacing: 0px;">
            <tbody>
                <tr style="font-size: 9px;" >
                    <td><b>Наименование:</b></td>
                    <td>{{ $pickUp->good->name->NAME }}</td>
                </tr>
                <tr style="font-size: 9px;">
                    <td><b>Покупатель:</b></td>
                    <td>{{ $invoice->buyer->SHORTNAME }}</td>
                </tr>
                <tr style="font-size: 13px;">
                    <td><b>Номер счета:</b></td>
                    <td>{{ $invoice->NS }}</td>
                </tr>
                <tr style="font-size: 13px;">
                    <td><b>Количество:</b></td>
                    <td>{{ $pickUp->QUANSKLADNEED - $pickUp->QUANSKLAD }}</td>
                </tr>
                <tr style="font-size: 7px;">
                    <td><b>Подобрано:</b></td>
                    <td>{{ \Carbon\Carbon::now()->format('d.m.Y H:i') }}</td>
                </tr>
                <tr style="font-size: 7px;">
                    <td><b>Сотрудник</b></td>
                    <td>{{ $employee->FULLNAME }}</td>
                </tr>
            </tbody>
        </table>
        </div>
        @endforeach
    </body>
</html>
