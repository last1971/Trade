<template>
    <v-container>
    <v-row>
        <v-col :cols="6 * rows">
            <span>{{ value.name.NAME }}</span>
            <span v-if="value.BODY">/ {{ value.BODY }}</span>
            <span v-if="value.PRODUCER">/ {{ value.PRODUCER }}</span>
        </v-col>
        <v-col :cols="6 * rows">
            <v-badge :color="color(quantity(value))" :content="quantity(value)" inline>
                есть
            </v-badge>
            <v-badge :color="color(quantity(value.reservesQuantity.toString()))"
                     :content="value.reservesQuantity.toString()"
                     class="ml-2"
                     inline
            >
                резерв
            </v-badge>
            <v-badge :color="color(futureReserve(value))" :content="futureReserve(value)" class="ml-2" inline>
                нада
            </v-badge>
            <v-badge :color="color(orderStep(value))" :content="orderStep(value)" class="ml-2" inline>
                порог
            </v-badge>
        </v-col>
    </v-row>
    </v-container>
</template>

<script>
    export default {
        name: "GoodInString",
        props: {
            value: {
                type: Object,
            },
            rows: {
                type: Number,
                default: 1,
            }
        },
        methods: {
            quantity(item) {
                return (
                    (item.warehouse ? item.warehouse.QUAN : 0) + (item.retailStore ? item.retailStore.QUAN : 0)
                ).toString();
            },
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
