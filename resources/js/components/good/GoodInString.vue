<template>
    <v-container>
        <v-row>
            <v-col :cols="rows === 2 ? 12 : 5">
                <span>{{ value.name.NAME }}</span>
                <span v-if="value.BODY">/ {{ value.BODY }}</span>
                <span v-if="value.PRODUCER">/ {{ value.PRODUCER }}</span>
                <br/>
                <div class="font-italic" style="font-size: 8px">{{ remark }}</div>
            </v-col>
            <v-col :cols="rows === 2 ? 12 : 7" class="d-flex flex-nowrap">
                <good-quantity :good="value"/>
                <reserves-modal :value="value" x-small plain rounded :name="value.name.NAME" is-not-future>
                    <template v-slot:button>
                        <v-badge :color="color(value.reservesQuantity.toString())"
                                 :content="value.reservesQuantity.toString()"
                                 inline
                        >
                            рез.
                        </v-badge>
                    </template>
                </reserves-modal>
                <reserves-modal :value="value" x-small plain rounded :name="value.name.NAME" :is-not-future="false">
                    <template v-slot:button>
                        <v-badge :color="color(futureReserve(value).toString())" :content="futureReserve(value)" inline>
                            надо
                        </v-badge>
                    </template>
                </reserves-modal>
                <order-line-in-way-modal v-if="isInWay" :value="value" x-small plain rounded :name="value.name.NAME">
                    <template v-slot:button>
                        <v-badge :color="color(transitQuantity.toString())" :content="transitQuantity.toString()"
                                 inline>
                            путь
                        </v-badge>
                    </template>
                </order-line-in-way-modal>
                <v-badge v-else :color="color(orderStep(value))" :content="orderStep(value)" class="ml-2" inline>
                    порог
                </v-badge>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
import GoodQuantity from "./GoodQuantity";
import ReservesModal from "../ReservesModal";
import OrderLineInWayModal from "../order/OrderLineInWayModal";

export default {
    name: "GoodInString",
    components: {OrderLineInWayModal, ReservesModal, GoodQuantity},
    props: {
        value: {
            type: Object,
        },
        rows: {
            type: Number,
            default: 1,
        }
    },
    computed: {
        isInWay() {
            return process.env.MIX_GOOD_IN_WAY;
        },
        transitQuantity() {
            return this.value.orderLinesTransitQuantity
                ? this.value.orderLinesTransitQuantity
                - this.value.shopLinesTransitQuantity
                - this.value.storeLinesTransitQuantity
                : 0
        },
        remark() {
            return this.value.PRIM.trim() && this.value.DESCRIPTION.trim()
                ? this.value.PRIM.trim() + ' / ' + this.value.DESCRIPTION.trim()
                : this.value.PRIM.trim() ? this.value.PRIM.trim() : this.value.DESCRIPTION.trim();
        },
    },
    methods: {
        futureReserve(item) {
            return (
                (item.invoiceLinesQuantityTransit
                        ? item.invoiceLinesQuantityTransit
                        - item.pickUpsTransitQuantity
                        - item.reservesQuantityTransit
                        : 0
                ) + item.retailOrderLinesNeedQuantity
            ).toString();
        },
        orderStep(value) {
            return value.orderStep
                ? value.orderStep.QUAN_TO_ZAKAZ_SHOP
                    ? value.orderStep.QUAN_TO_ZAKAZ_SHOP.toString()
                    : '0'
                : '0';
        },
        color(v) {
            return v !== '0' ? 'green' : 'red';
        }
    }
}
</script>

<style scoped>

</style>
