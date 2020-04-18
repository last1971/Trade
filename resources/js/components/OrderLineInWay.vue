<template>
    <v-data-table
        :headers="headers"
        :items="items"
        :loading="loading"
        :options.sync="options"
        :server-items-length="total"
        class="elevation-1"
        hide-default-footer
        item-key="ID"
        loading-text="Loading... Please wait"
    >
        <template v-slot:top>
            <div class="title ml-2">В пути(Заказах)</div>
        </template>
        <template v-slot:item.order.INVOICE_DATA="{ item }">
            {{ item.order.INVOICE_DATA | formatDate }}
        </template>
        <template v-slot:item.order.INVOICE_NUM="{ item }">
            <router-link :to="{ name: 'order', params: { id: item.MASTER_ID } }">
                {{ item.order.INVOICE_NUM }}
            </router-link>
        </template>
        <template v-slot:item.QUAN="{ item }">
            {{ item.QUAN - item.storeLinesQuantity - item.shopLinesQuantity }}
        </template>
        <template v-slot:item.PRICE="{ item}">
            {{ item.PRICE | formatRub }}
        </template>
        <template v-slot:item.SUMMAP="{ item}">
            {{ item.SUMMAP | formatRub }}
        </template>
        <template v-slot:item.DATA_PRIH="{ item }">
            <div :class="{ 'red--text': new Date(item.DATA_PRIH || item.order.DATA_PRIH) < new Date }">
                {{ (item.DATA_PRIH || item.order.DATA_PRIH) | formatDate }}
            </div>
        </template>
    </v-data-table>
</template>

<script>
    import tableMixin from "../mixins/tableMixin";

    export default {
        name: "OrderLineInWay",
        props: {
            invoiceLine: {
                type: Object,
                required: true,
            }
        },
        mixins: [tableMixin],
        data() {
            return {
                options: {
                    with: ['order', 'seller'],
                    aggregateAttributes: ['storeLinesQuantity', 'shopLinesQuantity'],
                    filterAttributes: [
                        'inWay', 'GOODSCODE'
                    ],
                    filterOperators: ['=', '='],
                    filterValues: [0, this.invoiceLine.GOODSCODE],
                    itemsPerPage: -1,
                },
                mobileFiltersVisible: false,
                dependent: true,
            }
        },
        computed: {
            headers() {
                return this.$store.getters['AUTH/HAS_PERMISSION']('order-line.full') ?
                    [
                        {text: 'Дата', value: 'order.INVOICE_DATA'},
                        {text: 'Номер', value: 'order.INVOICE_NUM'},
                        {text: 'Поставщик', value: 'seller.NAMEPOST'},
                        {text: 'В пути', value: 'QUAN', align: 'right'},
                        {text: 'Цена', value: 'PRICE', align: 'right'},
                        {text: 'Сумма', value: 'SUMMAP', align: 'right'},
                        {text: 'Ожидаем', value: 'DATA_PRIH'},
                    ] :
                    [
                        {text: 'В пути', value: 'QUAN', align: 'right'},
                        {text: 'Ожидаем', value: 'DATA_PRIH'},
                    ]

            },
            model() {
                return 'ORDER-LINE';
            },
        }
    }
</script>

<style scoped>

</style>
