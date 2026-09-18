<template>
    <v-data-table
        :headers="headers"
        :hide-default-footer="true"
        :items="items"
        :loading="loading"
        :options.sync="options"
        :server-items-length="total"
        :item-key="itemKey"
        :loading-text="loadingText"
    >
        <template v-slot:item.good.name.NAME="{ item }">
            <good-name :value="item.good" :prim="false" v-if="item.good"/>
        </template>
        <template v-slot:item.entry.PRICE="{ item }">
            <span v-if="item.entry">{{ item.entry.PRICE | formatRub }}</span>
        </template>
        <template v-slot:item.summap="{ item }">
            <span v-if="item.entry">{{ item.entry.PRICE * item.QUAN | formatRub }}</span>
        </template>
        <template v-slot:item.orderLine.MASTER_ID="{ item }">
            <router-link :to="{ name: 'order', params: { id: item.orderLine.MASTER_ID } }"
                         v-if="item.orderLine"
            >
                №{{ item.orderLine.MASTER_ID }}<template v-if="item.orderLine.order"> от {{ item.orderLine.order.DATA_ZAK | formatDate }}</template>
            </router-link>
        </template>
    </v-data-table>
</template>

<script>
import GoodName from "../good/GoodName";
import tableMixin from "../../mixins/tableMixin";

export default {
    name: "StoreInLines",
    components: {GoodName},
    mixins: [tableMixin],
    props: {
        value: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            options: {
                with: ['good.name', 'entry', 'orderLine.order'],
                filterAttributes: ['NP'],
                filterOperators: ['='],
                filterValues: [this.value.NP],
                sortBy: [process.env.MIX_IS_ELECTRONICA === 'true' ? 'SHOPINCODE' : 'SKLADINCODE'],
                sortDesc: [false],
                itemsPerPage: -1,
            },
            dependent: true,
            model: 'STORE-LINE',
        }
    },
    computed: {
        itemKey() {
            return this.$store.getters['STORE-LINE/KEY'];
        },
        // ГТД и страна есть только в складском приходе (SKLADIN); в магазинном
        // SHOPIN таких колонок нет, поэтому в рознице их не показываем.
        headers() {
            const isShop = process.env.MIX_IS_ELECTRONICA === 'true';
            return [
                {text: 'Товар', value: 'good.name.NAME', sortable: false},
                {text: 'Кол.', value: 'QUAN', align: 'right', sortable: false},
                {text: 'Цена', value: 'entry.PRICE', align: 'right', sortable: false},
                {text: 'Сумма', value: 'summap', align: 'right', sortable: false},
                ...(isShop ? [] : [
                    {text: 'ГТД', value: 'GTD', sortable: false},
                    {text: 'Страна', value: 'STRANA', sortable: false},
                ]),
                {text: 'Заказ', value: 'orderLine.MASTER_ID', sortable: false},
            ];
        },
    },
}
</script>

<style scoped>

</style>
