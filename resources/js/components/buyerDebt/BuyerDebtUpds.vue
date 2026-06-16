<template>
    <v-data-table
        :headers="headers"
        :items="upds"
        hide-default-footer
        disable-pagination
        :items-per-page="-1"
        dense
    >
        <template v-slot:item.NS="{ item }">
            <router-link :to="{ name: 'invoice', params: { id: item.SCODE } }">{{ item.NS }}</router-link>
        </template>
        <template v-slot:item.NSF="{ item }">
            <router-link :to="{ name: 'transfer-out', params: { id: item.SFCODE } }">{{ item.NSF }}</router-link>
        </template>
        <template v-slot:item.DATA="{ item }">{{ item.DATA | formatDate }}</template>
        <template v-slot:item.summa="{ item }">{{ item.summa | formatRub }}</template>
        <template v-slot:item.paid="{ item }">{{ item.paid | formatRub }}</template>
        <template v-slot:item.remaining="{ item }">{{ item.remaining | formatRub }}</template>
        <template v-slot:body.append>
            <tr class="font-weight-bold">
                <td>Итого</td>
                <td></td>
                <td></td>
                <td class="text-right">{{ totals.summa | formatRub }}</td>
                <td class="text-right">{{ totals.paid | formatRub }}</td>
                <td class="text-right">{{ totals.remaining | formatRub }}</td>
                <td></td>
            </tr>
        </template>
    </v-data-table>
</template>

<script>
    export default {
        name: "BuyerDebtUpds",
        props: {
            invoices: {type: Array, default: () => []},
        },
        data: () => ({
            headers: [
                {text: 'Счёт', value: 'NS'},
                {text: 'УПД №', value: 'NSF'},
                {text: 'Дата', value: 'DATA'},
                {text: 'Сумма', value: 'summa', align: 'end'},
                {text: 'Оплачено', value: 'paid', align: 'end'},
                {text: 'Остаток', value: 'remaining', align: 'end'},
                {text: 'Статус', value: 'status'},
            ],
        }),
        computed: {
            // Неоплаченные УПД по всем счетам (как лист «Неоплаченные УПД» в Excel).
            upds() {
                return this.invoices.flatMap(inv =>
                    inv.upds
                        .filter(upd => upd.remaining > 0)
                        .map(upd => ({...upd, NS: inv.NS, SCODE: inv.SCODE}))
                );
            },
            totals() {
                return this.upds.reduce((acc, upd) => ({
                    summa: acc.summa + upd.summa,
                    paid: acc.paid + upd.paid,
                    remaining: acc.remaining + upd.remaining,
                }), {summa: 0, paid: 0, remaining: 0});
            },
        },
    }
</script>

<style scoped>

</style>
