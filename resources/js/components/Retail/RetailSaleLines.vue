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
                        <v-btn rounded color="primary" :disabled="disabled" @click="refund" :loading="refundLoading">
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
            refundLoading: false,
            model: 'RETAIL-SALE-LINE'
        }
    },
    computed: {
        disabled() {
            return this.selectedIds.length === 0;
        }
    },
    methods: {
        async refund() {
            try {
                this.refundLoading = true;
                const payload = {
                    selectedIds: this.selectedIds,
                    selectedQnts: this.selectedIds.map((id) => {
                        return this.$store.getters[this.model + '/GET'](id).QUANSHOP;
                    }),
                    datatime: this.value.DATATIME,
                    items: this.selectedIds.map((id) => {
                        const item = this.$store.getters[this.model + '/GET'](id);
                        return {
                            name: item.good.name.NAME,
                            quantity: item.QUANSHOP,
                            price: item.PRICE,
                            amount: item.AMOUNT,
                        };
                    }),
                    amount: this.selectedIds.reduce((acc, id) => {
                        const item = this.$store.getters[this.model + '/GET'](id);
                        return acc + parseFloat(item.AMOUNT);
                    }, 0),
                }
                await this.$store.dispatch(this.model + '/REFUND', payload);
                this.selectedIds.forEach((id) => {
                    const index = this.itemIds.indexOf(id);
                    this.itemIds.splice(index, 1);
                });
                this.selectedIds = [];
                const retailSale = _.cloneDeep(this.value);
                retailSale.SUMMA = this.items.reduce((acc, item) => acc + parseFloat(item.AMOUNT), 0);
                this.$store.commit('RETAIL-SALE/UPDATE', retailSale);
            } catch (e) {
                this.$store.commit('SNACKBAR/ERROR', e.response.data.message);
            }
            this.refundLoading = false;
        }
    }
}
</script>

<style scoped>

</style>
