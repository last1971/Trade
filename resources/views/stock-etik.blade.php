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
            <p style="font-size: 10px; text-align: center;">{{ $invoice->firm->FIRMNAME }}</p>
        <table style="font-size: 8px; border-spacing: 0px;">
            <tbody>
                <tr>
                    <td><b>Наименование:</b></td>
                    <td>{{ $pickUp->good->name->NAME }}</td>
                </tr>
                <tr>
                    <td><b>Корпус:</b></td>
                    <td>{{ $pickUp->good->BODY }}</td>
                </tr>
                <tr>
                    <td><b>Производитель:</b></td>
                    <td>{{ $pickUp->good->PRODUCER }}</td>
                </tr>
                <tr>
                    <td><b>Покупатель:</b></td>
                    <td>{{ $invoice->buyer->FULLNAME }}</td>
                </tr>
                <tr>
                    <td><b>Номер счета:</b></td>
                    <td>{{ $invoice->NS }}</td>
                </tr>
                <tr>
                    <td><b>Количество:</b></td>
                    <td>{{ $pickUp->QUANSKLADNEED - $pickUp->QUANSKLAD }}</td>
                </tr>
                <tr>
                    <td><b>Подобрано:</b></td>
                    <td>{{ \Carbon\Carbon::now()->format('d.m.Y H:i') }}</td>
                </tr>
                <tr>
                    <td><b>Сотрудник</b></td>
                    <td>{{ $employee->FULLNAME }}</td>
                </tr>
            </tbody>
        </table>
        </div>
        @endforeach
    </body>
</html>
