<template>
    <v-expansion-panels>
        <v-expansion-panel :disabled="disabled">
            <v-expansion-panel-header class="body-1">
                <div v-if="nothing">
                    Позиция отутствует на складах и в пути
                </div>
                <div v-else>
                    <span v-if="retailStoreQuantity">
                        В магазине <b>{{ retailStoreQuantity }}</b> {{ value.UNIT_I }},
                    </span>
                    <span v-if="warehouseQuantity">
                        на складе <b>{{ warehouseQuantity }}</b> {{ value.UNIT_I }},
                    </span>
                    <span v-if="retailStoreQuantity && warehouseQuantity">
                        всего <b>{{ retailStoreQuantity +  warehouseQuantity }}</b> {{ value.UNIT_I }},
                    </span>
                    <span v-if="retailStoreQuantity || warehouseQuantity">
                        свободно <b class="primary--text">{{ retailStoreQuantity +  warehouseQuantity - value.reservesQuantity }}</b> {{ value.UNIT_I }},
                    </span>
                    <span v-if="value.reservesQuantity">
                        в резерве <b>{{ value.reservesQuantity }}</b> {{ value.UNIT_I }},
                    </span>
                    <span v-if="invoiceNeedQuantity + value.retailOrderLinesNeedQuantity > 0">
                        нада <b>{{ invoiceNeedQuantity + value.retailOrderLinesNeedQuantity }}</b> {{ value.UNIT_I }},
                    </span>
                    <span v-if="transitQuantity">
                        в пути <b>{{ transitQuantity }}</b> {{ value.UNIT_I }},
                        свободно <b class="primary--text">{{ transitFreeQuantity }}</b> {{ value.UNIT_I }}
                    </span>
                </div>
            </v-expansion-panel-header>
            <v-expansion-panel-content>
                <reserves v-model="value"/>
                <v-divider/>
                <future-reserves v-model="value"/>
            </v-expansion-panel-content>
        </v-expansion-panel>
    </v-expansion-panels>
</template>

<script>
    import Reserves from "./Reserves";
    import FutureReserves from "./FutureStoreReserves";

    export default {
        name: "Leftovers",
        components: {FutureReserves, Reserves},
        props: {
            value: {
                type: Object,
                required: true,
            }
        },
        data() {
            return {
                loading: false,
            }
        },
        computed: {
            retailStoreQuantity() {
                return this.value.retailStore ? this.value.retailStore.QUAN : 0;
            },
            warehouseQuantity() {
                return this.value.warehouse ? this.value.warehouse.QUAN : 0;
            },
            invoiceNeedQuantity() {
                return this.value.invoiceLinesQuantityTransit
                    ? this.value.invoiceLinesQuantityTransit
                    - this.value.pickUpsTransitQuantity
                    - this.value.reservesQuantityTransit
                    : 0
            },
            transitQuantity() {
                return this.value.orderLinesTransitQuantity
                    ? this.value.orderLinesTransitQuantity
                    - this.value.shopLinesTransitQuantity
                    - this.value.storeLinesTransitQuantity
                    : 0
            },
            transitFreeQuantity() {
                return this.value.orderLinesTransitQuantity
                    ? this.value.orderLinesTransitQuantity
                    - this.value.shopLinesTransitQuantity
                    - this.value.storeLinesTransitQuantity
                    - (
                        this.value.invoiceLinesQuantityTransit
                            ? this.value.invoiceLinesQuantityTransit
                            - this.value.pickUpsTransitQuantity
                            - this.value.reservesQuantityTransit
                            : 0
                    )
                    - this.value.retailOrderLinesNeedQuantity
                    : 0
            },
            nothing() {
                return !this.retailStoreQuantity && !this.warehouseQuantity && !this.value.reservesQuantity
                    && !this.transitQuantity
                    && (this.invoiceNeedQuantity + this.value.retailOrderLinesNeedQuantity === 0);
            },
            disabled() {
                return !this.value.reservesQuantity && !this.transitQuantity
                    && (this.invoiceNeedQuantity + this.value.retailOrderLinesNeedQuantity === 0);
            }
        }
    }
</script>

<style scoped>

</style>
