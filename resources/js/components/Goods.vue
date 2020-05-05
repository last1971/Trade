<template>
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
        item-key="GODDSCODE"
        loading-text="Loading... Please wait"
    >
        <template v-slot:top>
            <div class="m-2">
                <v-text-field label="Наименовние" v-model="searchName"/>
            </div>
        </template>
        <template v-slot:item.retail_price="{ item }">
            <div v-if="item.retail_price && parseFloat(item.retail_price.PRICEROZN) > 0">
                1: {{ item.retail_price.PRICEROZN | formatRub }}
            </div>
            <div v-if="item.retail_price && parseFloat(item.retail_price.PRICEMOPT)">
                {{ item.retail_price.QUANMOPT }}: {{ item.retail_price.PRICEMOPT | formatRub }}
            </div>
            <div v-if="item.retail_price && parseFloat(item.retail_price.PRICEOPT)">
                {{ item.retail_price.QUANOPT }}: {{ item.retail_price.PRICEOPT | formatRub }}
            </div>
        </template>
        <template v-slot:item.warehouse="{ item }">
            {{ item.warehouse ? item.warehouse.QUAN : 0 }} /
            {{ item.retail_store ? item.retail_store.QUAN : 0 }} /
            <b class="primary--text">
                {{
                (item.warehouse ? item.warehouse.QUAN : 0) + (item.retail_store ? item.retail_store.QUAN : 0)
                - item.reservesQuantity
                }}
            </b>
        </template>
        <template v-slot:item.reservesQuantity="{ item }">
            {{ item.reservesQuantity }} /
            {{
            (item.invoiceLinesQuantityTransit
            ? item.invoiceLinesQuantityTransit - item.pickUpsTransitQuantity - item.reservesQuantityTransit : 0)
            + item.retailOrderLinesNeedQuantity
            }}
        </template>
        <template v-slot:item.orderLinesTransitQuantity="{ item }">
            {{
            item.orderLinesTransitQuantity
            ? item.orderLinesTransitQuantity - item.shopLinesTransitQuantity - item.storeLinesTransitQuantity
            : 0
            }} /
            {{
            item.orderLinesTransitQuantity ? item.orderLinesTransitQuantity - item.shopLinesTransitQuantity -
            item.storeLinesTransitQuantity - (item.invoiceLinesQuantityTransit ? item.invoiceLinesQuantityTransit -
            item.pickUpsTransitQuantity - item.reservesQuantityTransit : 0) - item.retailOrderLinesNeedQuantity : 0
            }}
        </template>
        <template v-slot:item.order_step="{ item }">
            <div v-if="item.order_step">
                {{ item.order_step.BOUND_QUAN_SHOP }} : {{ item.order_step.QUAN_TO_ZAKAZ_SHOP }}
            </div>
            <div v-if="item.order_step">
                {{ item.order_step.BOUND_QUAN_SKLAD }} : {{ item.order_step.QUAN_TO_ZAKAZ_SKLAD }}
            </div>
        </template>
    </v-data-table>
</template>

<script>
    import tableMixin from "../mixins/tableMixin";
    import tableOptionsRouteMixin from "../mixins/tableOptionsRouteMixin";
    import utilsMixin from "../mixins/utilsMixin";

    export default {
        name: "Goods",
        mixins: [tableMixin, tableOptionsRouteMixin, utilsMixin],
        data() {
            return {
                options: {
                    with: [
                        'retailPrice', 'orderStep', 'retailStore', 'warehouse', 'name', 'category', 'goodNames'
                    ],
                    aggregateAttributes: [
                        'reservesQuantity',
                        'invoiceLinesQuantityTransit',
                        'reservesQuantityTransit',
                        'pickUpsTransitQuantity',
                        'retailOrderLinesNeedQuantity',
                        'orderLinesTransitQuantity',
                        'shopLinesTransitQuantity',
                        'storeLinesTransitQuantity',
                    ],
                    filterAttributes: [
                        'goodNames.NAME',
                    ],
                    filterOperators: ['CONTAIN'],
                    filterValues: [''],
                },
                mobileFiltersVisible: false,
                model: 'GOOD',
                searchName: '',
            }
        },
        watch: {
            searchName(val) {
                this.options.filterValues.splice(0, 1, val.replace(/[^а-яёА-ЯЁa-zA-Z0-9]/g, ''));
            }
        },
        methods: {},
        beforeRouteEnter(to, from, next) {
            next(vm => {
                vm.$store.commit('BREADCRUMBS/SET', [
                    {
                        text: 'Торговля',
                        to: {name: 'home'},
                        exact: true,
                        disabled: false,
                    },
                    {
                        text: 'Товары',
                        to: {name: 'goods'},
                        exact: true,
                        disabled: true,
                    }
                ]);
            });
        }
    }
</script>

<style scoped>

</style>
