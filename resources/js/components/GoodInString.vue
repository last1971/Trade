<template>
    <v-row>
        <v-col>
            <span>{{ value.name.NAME }}</span>
            <span v-if="value.BODY">/ {{ value.BODY }}</span>
            <span v-if="value.PRODUCER">/ {{ value.PRODUCER }}</span>
        </v-col>
        <v-col>
            <v-badge :color="color(quantity(value))" :content="quantity(value)">
                есть
            </v-badge>
            <v-badge :color="color(quantity(value.reservesQuantity.toString()))"
                     :content="value.reservesQuantity.toString()"
                     class="ml-2"
            >
                резерв
            </v-badge>
            <v-badge :color="color(futureReserve(value))" :content="futureReserve(value)" class="ml-2">
                нада
            </v-badge>
            <v-badge :color="color(orderStep(value))" :content="orderStep(value)" class="ml-2">
                порог
            </v-badge>
        </v-col>
    </v-row>
</template>

<script>
    export default {
        name: "GoodInString",
        props: ['value'],
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
