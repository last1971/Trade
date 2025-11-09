<template>
    <v-card class="pt-2">
        <v-row>
            <v-col>
            <v-tabs v-model="tab" grow>
                <v-tabs-slider></v-tabs-slider>
                <v-tab>Счета</v-tab>
                <v-tab>УПД</v-tab>
                <v-tab v-if="hasPermission('reserve.index')">Резервы</v-tab>
                <v-tab v-if="hasPermission('store-line.index')">Приходы</v-tab>
                <v-tab>ЕдетЪ</v-tab>
            </v-tabs>
            </v-col>
            <v-col cols="1">
                <v-btn @click="$emit('close')" icon class="mt-2">
                    <v-icon color="red">
                        mdi-close
                    </v-icon>
                </v-btn>
            </v-col>
        </v-row>
        <v-tabs-items v-model="tab">
            <v-tab-item>
                <invoice-lines-depennt-by-good :value="value"/>
            </v-tab-item>
            <v-tab-item>
                <transfer-out-lines-dependent v-if="good" :good="good"/>
            </v-tab-item>
            <v-tab-item v-if="hasPermission('reserve.index')">>
                <reserves-dependent v-if="good && good.name" :value="good" :name="good.name.NAME"/>
            </v-tab-item>
            <v-tab-item v-if="hasPermission('store-line.index')">
                <store-lines-dependent v-if="good" :good="good"/>
            </v-tab-item>
            <v-tab-item>
                <order-line-in-way v-if="good" :invoice-line="good" class="mx-2" :top-text="false" />
            </v-tab-item>
        </v-tabs-items>
    </v-card>
</template>

<script>

import InvoiceLinesDepenntByGood from "../invoice/InvoiceLinesDepenntByGood";
import TransferOutLinesModal from "../transferOut/TransferOutLinesModal";
import TransferOutLinesDependent from "../transferOut/TransferOutLinesDependent";
import ReservesDependent from "../ReservesDependent";
import StoreLinesDependent from "../StoreLinesDependent";
import OrderLineInWay from "../order/OrderLineInWay";
import {mapGetters} from "vuex";
export default {
    name: "GoodInfo",
    components: {
        OrderLineInWay,
        StoreLinesDependent,
        ReservesDependent, TransferOutLinesDependent, TransferOutLinesModal, InvoiceLinesDepenntByGood},
    props: {
        value: {
            type: Number,
            required: true,
        },
    },
    data() {
        return {
            tab: null,
            query: {
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
            }
        }
    },
    computed: {
        good() {
            return this.$store.getters['GOOD/GET'](this.value);
        },
        ...mapGetters({ hasPermission: 'AUTH/HAS_PERMISSION' }),
    },
    async created() {
        if (!this.good) this.$store.dispatch('GOOD/GET', { id: this.value, query: this.query });
    },
}
</script>

<style scoped>

</style>
