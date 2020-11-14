<template>
    <v-data-table
        :headers="headers"
        :hide-default-footer="true"
        show-select
        :items="items"
        :loading="loading"
        :multi-sort="true"
        :options.sync="options"
        :server-items-length="total"
        item-key="SHOPLOGCODE"
        :loading-text="loadingText"
        @item-selected="selectItem"
        @toggle-select-all="selectItems"
    >
        <template v-slot:top>
            <v-container>
                <v-row>
                    <v-col>
                        <v-btn rounded color="primary">
                            Возврат
                            <v-icon>mdi-credit-card-refund-outline</v-icon>
                        </v-btn>
                    </v-col>
                </v-row>
            </v-container>
        </template>
        <template v-slot:item.good.name.NAME="{ item }">
            <good-name v-model="item.good" :prim="item.good.PRIM"/>
        </template>
        <template v-slot:item.PRICE="{ item }">
            {{ item.PRICE | formatRub }}
        </template>
        <template v-slot:item.AMOUNT="{ item }">
            {{ item.AMOUNT | formatRub }}
        </template>
        <template v-slot:item.DISCOUNT="{ item }">
            {{ item.DISCOUNT | formatPercent }}
        </template>
    </v-data-table>
</template>

<script>

import GoodName from "../good/GoodName";
import tableMixin from "../../mixins/tableMixin";
import moment from 'moment';

export default {
    name: "RetailSaleLines",
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
                with: ['good.name', 'good.category'],
                filterAttributes: [
                    'USERNAME', 'DATATIME'
                ],
                filterOperators: ['=', 'BETWEENDATE'],
                filterValues: [
                    this.value.USERNAME,
                    [
                        this.value.DATATIME,
                        moment(this.value.DATATIME).add(1, 'seconds').format('Y-MM-DD H:mm:ss')
                    ],
                ],
                itemsPerPage: -1,
            },
            dependent: true,
            model: 'RETAIL-SALE-LINE'
        }
    }
}
</script>

<style scoped>

</style>
