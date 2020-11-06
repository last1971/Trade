<template>
    <v-data-table
        :footer-props="{
            showFirstLastPage: true,
        }"
        :headers="mutatedHeaders"
        :items="items"
        :loading="loading"
        :multi-sort="true"
        :options.sync="options"
        :server-items-length="total"
        item-key="ID"
        loading-text="Идут разгрузочно-погрузочные работы... будь пациентом"
    >
        <template v-slot:top>
            <v-container>
            <v-row>
                <v-col>
                    <buyer-select v-model="buyer"/>
                </v-col>
                <v-col>
                    <retail-order-line-status-select v-model="status"/>
                </v-col>
                <v-col>
                    <v-switch v-model="onlyReserves" inset :label="onlyReserves ? 'Только резервы' : 'Все'"/>
                </v-col>
                <v-col>
                    <v-btn :disabled="options.itemsPerPage > 0" rounded @click="toList" class="mt-3">
                        Перенести в список
                        <v-icon class="ml-2">mdi-cart-arrow-down</v-icon>
                    </v-btn>
                </v-col>
            </v-row>
            </v-container>
        </template>
        <template v-slot:item.retailOrder.DATA="{ item }">
            {{ item.retailOrder.DATA | formatDate }}
        </template>
        <template v-slot:item.ROZN_DETAIL.STATUS="{ item }">
            {{ $store.getters['RETAIL-ORDER-STATUS/GET-STATUS'](item.STATUS) }}
        </template>
        <template v-slot:item.PRICE="{ item }">
            {{ item.PRICE | formatRub }}
        </template>
    </v-data-table>
</template>

<script>
import tableMixin from "../mixins/tableMixin";
import utilsMixin from "../mixins/utilsMixin";
import BuyerSelect from "./BuyerSelect";
import buyer from "../store/buyer";
import RetailOrderLineStatusSelect from "./RetailOrderLineStatusSelect";

export default {
    name: "RetailOrderLines",
    components: {RetailOrderLineStatusSelect, BuyerSelect},
    mixins: [tableMixin, utilsMixin],
    data() {
        return {
            options: {
                with: ['retailOrder.buyer', 'good.name', 'good.retailStore'],
                // aggregateAttributes: [
                //     'shopLinesQuantity', 'storeLinesQuantity',
                // ],
                filterAttributes: [
                    'retailOrderLinesNotClosed',
                    'ROZN_DETAIL.STATUS',
                ],
                filterOperators: ['=', 'IN'],
                filterValues: [1, [0]],
                // itemsPerPage: -1,
            },
            mobileFiltersVisible: false,
            dependent: false,
            model: 'RETAIL-ORDER-LINE',
            removeHeaders: ['ROZN_DETAIL.STATUS'],
            buyer: null,
            status: [0],
            onlyReserves: false,
            saveItemsPerPage: 10,
        }
    },
    computed: {
        mutatedHeaders() {
            return this.headers.filter(
                (header) => this.removeHeaders.find((rh) => rh === header.value) === undefined
            );

        },
    },
    watch: {
        buyer(v) {
            const index = this.options.filterAttributes.indexOf('retailOrder.POKUPATCODE');
            if (v && index < 0) {
                this.options.filterAttributes.push('retailOrder.POKUPATCODE');
                this.options.filterOperators.push('=');
                this.options.filterValues.push(v);
            } else if (v) {
                this.options.filterValues.splice(index, 1, v);
            } else {
                this.options.filterAttributes.splice(index, 1);
                this.options.filterOperators.splice(index, 1);
                this.options.filterValues.splice(index, 1);
            }
            const i = this.removeHeaders.indexOf('retailOrder.buyer.SHORTNAME');
            if (v && i < 0) this.removeHeaders.push('retailOrder.buyer.SHORTNAME');
            else if (!v && i >= 0) this.removeHeaders.splice(i, 1);
            this.changePerPage()
        },
        status(v) {
            const i = this.options.filterAttributes.indexOf('ROZN_DETAIL.STATUS');
            this.options.filterValues.splice(i, 1, v);
            const index = this.removeHeaders.indexOf('ROZN_DETAIL.STATUS');
            if (v.length === 1) {
                if (index < 0) this.removeHeaders.push('ROZN_DETAIL.STATUS');
            } else {
                if (index >= 0) this.removeHeaders.splice(index, 1)
            }
        },
        onlyReserves(v) {
            const i = this.options.filterAttributes.indexOf('QUAN_RES');
            if (v && i < 0) {
                this.options.filterAttributes.push('QUAN_RES');
                this.options.filterOperators.push('>');
                this.options.filterValues.push(0);
            } else if (!v && i >= 0) {
                this.options.filterAttributes.splice(i, 1);
                this.options.filterOperators.splice(i, 1);
                this.options.filterValues.splice(i, 1);
            }
            this.changePerPage()
        }
    },
    methods: {
        changePerPage() {
            if (this.buyer && this.onlyReserves) {
                this.saveItemsPerPage = this.options.itemsPerPage;
                this.options.itemsPerPage = -1;
            } else {
                this.options.itemsPerPage = this.saveItemsPerPage;
            }
        },
        toList() {
            this.$store.commit('GOODS-LIST/OPENED', false);
            this.$store.commit('GOODS-LIST/OPENED', true);
            this.$store.commit('GOODS-LIST/BUYER-ID', this.buyer);
            const goods = [];
            this.items.forEach((item) => {
                goods.push(_.cloneDeep(item.good));
                const payload = {
                    GOODSCODE: item.GOODSCODE,
                    quantity: item.QUAN_RES,
                    price: item.PRICE,
                    discount: (1 - item.PRICE / item.PRICEBASE) * 100,
                    amount: item.PRICE * item.QUAN_RES,
                    retailOrderLineId: item.ID,
                };
                this.$store.commit('GOODS-LIST/PUSH', payload);
            });
            this.$store.commit('GOOD/MERGE', goods);
            this.$store.commit('GOODS-LIST/RENDER');
            this.$router.push({ name: 'goods-list'});
        }
    }
}
</script>

<style scoped>

</style>
