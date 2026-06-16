<template>
    <v-data-table
        :headers="headers"
        :items="holes"
        hide-default-footer
        disable-pagination
        :items-per-page="-1"
        dense
    >
        <template v-slot:item.NS="{ item }">
            <router-link :to="{ name: 'invoice', params: { id: item.SCODE } }">{{ item.NS }}</router-link>
        </template>
        <template v-slot:item.name="{ item }">
            <good-name :value="{ GOODSCODE: item.GOODSCODE, name: { NAME: item.name }, QUAN: item.qty }" :prim="false"/>
        </template>
        <template v-slot:item.price="{ item }">{{ item.price | formatRub }}</template>
        <template v-slot:item.sum="{ item }">{{ item.sum | formatRub }}</template>
        <template v-slot:body.append>
            <tr class="font-weight-bold">
                <td>Итого</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="text-right">{{ total | formatRub }}</td>
            </tr>
        </template>
    </v-data-table>
</template>

<script>
    import GoodName from "../good/GoodName";

    export default {
        name: "BuyerDebtHoles",
        components: {GoodName},
        props: {
            invoices: {type: Array, default: () => []},
        },
        data: () => ({
            headers: [
                {text: 'Счёт', value: 'NS'},
                {text: 'GOODSCODE', value: 'GOODSCODE'},
                {text: 'Товар', value: 'name'},
                {text: 'Корпус', value: 'body'},
                {text: 'Производитель', value: 'producer'},
                {text: 'Кол-во', value: 'qty', align: 'end'},
                {text: 'Цена', value: 'price', align: 'end'},
                {text: 'Сумма', value: 'sum', align: 'end'},
            ],
        }),
        computed: {
            // Товары-дыры по всем счетам (как лист «Не едет» в Excel).
            holes() {
                return this.invoices.flatMap(inv => inv.holes);
            },
            total() {
                return this.holes.reduce((acc, hole) => acc + hole.sum, 0);
            },
        },
    }
</script>

<style scoped>

</style>
