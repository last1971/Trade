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
                <v-row>
                    <v-col>
                        <v-text-field label="Наименовние" v-model="searchName"/>
                    </v-col>
                    <v-col>
                        <v-switch
                            v-model="inRetailStore"
                            :label="inRetailStore ? 'Есть в магазине' : 'Все в магазине'"
                        ></v-switch>
                    </v-col>
                </v-row>
            </div>
        </template>
        <template v-slot:item.name.NAME="{ item }">
            <good-name v-model="item"/>
        </template>
        <template v-slot:item.retailPrice="{ item }">
            <div v-if="item.retailPrice && parseFloat(item.retailPrice.PRICEROZN) > 0">
                1: {{ item.retailPrice.PRICEROZN | formatRub }}
            </div>
            <div v-if="item.retailPrice && parseFloat(item.retailPrice.PRICEMOPT)">
                {{ item.retailPrice.QUANMOPT }}: {{ item.retailPrice.PRICEMOPT | formatRub }}
            </div>
            <div v-if="item.retailPrice && parseFloat(item.retailPrice.PRICEOPT)">
                {{ item.retailPrice.QUANOPT }}: {{ item.retailPrice.PRICEOPT | formatRub }}
            </div>
        </template>
        <template v-slot:item.warehouse="{ item }">
            <good-to-list v-model="item"/>
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
        <template v-slot:item.orderStep="{ item }">
            <div v-if="item.orderStep">
                {{ item.orderStep.BOUND_QUAN_SHOP }} : {{ item.orderStep.QUAN_TO_ZAKAZ_SHOP }}
            </div>
            <div v-if="item.orderStep">
                {{ item.orderStep.BOUND_QUAN_SKLAD }} : {{ item.orderStep.QUAN_TO_ZAKAZ_SKLAD }}
            </div>
        </template>
    </v-data-table>
</template>

<script>
    import tableMixin from "../../mixins/tableMixin";
    import tableOptionsRouteMixin from "../../mixins/tableOptionsRouteMixin";
    import utilsMixin from "../../mixins/utilsMixin";
    import GoodName from "./GoodName";
    import GoodToList from "./GoodToList";

    export default {
        name: "Goods",
        components: {GoodToList, GoodName},
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
                        'goodNames.NAME', 'HIDDEN', 'retailStore.QUAN'
                    ],
                    filterOperators: ['CONTAIN', '=', '>'],
                    filterValues: ['', 0, 0],
                },
                mobileFiltersVisible: false,
                model: 'GOOD',
                searchName: '',
                inRetailStore: true,
            }
        },
        watch: {
            searchName(val) {
                this.options.filterValues.splice(0, 1, val.replace(/[^а-яёА-ЯЁa-zA-Z0-9]/g, ''));
            },
            inRetailStore(val) {
                this.options.filterValues.splice(2, 1, val ? 0 : -1)
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
