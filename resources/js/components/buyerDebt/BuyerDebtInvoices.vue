<template>
    <div>
        <v-data-table
            :headers="headers"
            :items="invoices"
            hide-default-footer
            disable-pagination
            :items-per-page="-1"
            dense
        >
            <template v-slot:item.NS="{ item }">
                <router-link :to="{ name: 'invoice', params: { id: item.SCODE } }">{{ item.NS }}</router-link>
            </template>
            <template v-slot:item.DATA="{ item }">{{ item.DATA | formatDate }}</template>
            <template v-slot:item.sum="{ item }">{{ item.sum | formatRub }}</template>
            <template v-slot:item.paidBank="{ item }">{{ item.paidBank | formatRub }}</template>
            <template v-slot:item.deposit="{ item }">{{ item.deposit | formatRub }}</template>
            <template v-slot:item.shipped="{ item }">{{ item.shipped | formatRub }}</template>
            <template v-slot:item.debt="{ item }">{{ item.debt | formatRub }}</template>
            <template v-slot:item.remainingToShip="{ item }">{{ item.remainingToShip | formatRub }}</template>
            <template v-slot:item.onHand="{ item }">{{ item.onHand | formatRub }}</template>
            <template v-slot:item.coming="{ item }">{{ item.coming | formatRub }}</template>
            <template v-slot:item.notComing="{ item }">{{ item.notComing | formatRub }}</template>
            <template v-slot:body.append>
                <tr class="font-weight-bold">
                    <td>Итого</td>
                    <td></td>
                    <td></td>
                    <td class="text-right">{{ totals.sum | formatRub }}</td>
                    <td class="text-right">{{ totals.paidBank | formatRub }}</td>
                    <td class="text-right">{{ totals.deposit | formatRub }}</td>
                    <td class="text-right">{{ totals.shipped | formatRub }}</td>
                    <td class="text-right">{{ totals.debt | formatRub }}</td>
                    <td class="text-right">{{ totals.remainingToShip | formatRub }}</td>
                    <td class="text-right">{{ totals.onHand | formatRub }}</td>
                    <td class="text-right">{{ totals.coming | formatRub }}</td>
                    <td class="text-right">{{ totals.notComing | formatRub }}</td>
                </tr>
            </template>
        </v-data-table>
        <div class="pa-4 text-right">
            <div class="font-weight-bold">Должны денег: {{ totals.debt | formatRub }}</div>
            <div class="font-weight-bold">Конкретно должны: {{ totals.oweConcrete | formatRub }}</div>
        </div>
    </div>
</template>

<script>
    export default {
        name: "BuyerDebtInvoices",
        props: {
            invoices: {type: Array, default: () => []},
            totals: {type: Object, default: () => ({})},
        },
        data: () => ({
            headers: [
                {text: 'Счёт', value: 'NS'},
                {text: 'Дата', value: 'DATA'},
                {text: 'Статус', value: 'statusLabel'},
                {text: 'Сумма', value: 'sum', align: 'end'},
                {text: 'Оплачено', value: 'paidBank', align: 'end'},
                {text: 'Депозит', value: 'deposit', align: 'end'},
                {text: 'Отгружено', value: 'shipped', align: 'end'},
                {text: 'Долг', value: 'debt', align: 'end'},
                {text: 'К отгрузке', value: 'remainingToShip', align: 'end'},
                {text: 'На складе', value: 'onHand', align: 'end'},
                {text: 'Едет', value: 'coming', align: 'end'},
                {text: 'Не едет', value: 'notComing', align: 'end'},
            ],
        }),
    }
</script>

<style scoped>

</style>
