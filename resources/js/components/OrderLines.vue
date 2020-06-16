<template>
    <div>
        <v-data-table
            :footer-props="{
            showFirstLastPage: true,
        }"
            :headers="headers"
            :items="items"
            :loading="loading"
            :multi-sort="true"
            :options.sync="options"
            :server-items-length="total"
            item-key="ID"
            loading-text="Loading... Please wait"
            v-if="isOrderImportLinesEmpty"
        >
            <template v-slot:top>
                <order-edit :value="value" @import="importOpen" @input="proxyInput"/>
            </template>
            <template v-slot:item.name.NAME="{ item }">
                <good-name :prim="item.good.PRIM" v-model="item"/>
            </template>
            <template v-slot:item.DATA_PRIH="{ item }">
                {{ (item.DATA_PRIH || value.DATA_PRIH) | formatDate }}
            </template>
            <template v-slot:item.inQuantity="{ item }">
                <div :class="inQuantityColor(item)">
                    {{ item.shopLinesQuantity + item.storeLinesQuantity }}
                </div>
            </template>
            <template v-slot:item.PRICE="{ item }">
                {{ item.PRICE | formatRub }}
            </template>
            <template v-slot:item.SUMMAP="{ item }">
                {{ item.SUMMAP | formatRub }}
            </template>
        </v-data-table>
        <order-import-lines v-else v-model="orderImportLines"/>
    </div>
</template>

<script>
    import tableMixin from "../mixins/tableMixin";
    import utilsMixin from "../mixins/utilsMixin";
    import OrderEdit from "./OrderEdit";
    import GoodName from "./GoodName";
    import OrderImportLines from "./OrderImportLines";

    export default {
        name: "OrderLines",
        mixins: [tableMixin, utilsMixin],
        components: {OrderImportLines, OrderEdit, GoodName},
        props: {
            value: {
                type: Object,
                required: true,
            }
        },
        data() {
            return {
                options: {
                    with: ['category', 'good', 'name'],
                    aggregateAttributes: [
                        'shopLinesQuantity', 'storeLinesQuantity',
                    ],
                    filterAttributes: [
                        'MASTER_ID',
                    ],
                    filterOperators: ['='],
                    filterValues: [this.value.ID],
                    itemsPerPage: -1,
                },
                mobileFiltersVisible: false,
                dependent: true,
                model: 'ORDER-LINE',
                orderImportLines: [],
            }
        },
        computed: {
            isOrderImportLinesEmpty() {
                return !this.orderImportLines.length;
            }
        },
        methods: {
            inQuantityColor(item) {
                if (item.shopLinesQuantity + item.storeLinesQuantity === 0) return 'red--text';
                if (item.shopLinesQuantity + item.storeLinesQuantity < item.QUAN) return 'primary--text';
                return 'success--text';
            },
            importOpen(orderImportLines) {
                if (_.isArray(orderImportLines) && !_.isEmpty(orderImportLines)) {
                    this.orderImportLines = orderImportLines
                }
            }
        },
    }
</script>

<style scoped>

</style>
