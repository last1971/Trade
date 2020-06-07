<template>
    <v-row>
        <v-col>
            {{ value.name.NAME }}
        </v-col>
        <v-col>
            <v-badge :content="quantity(value)">
                есть
            </v-badge>
            <v-badge :content="value.reservesQuantity.toString()" class="ml-2">
                резерв
            </v-badge>
            <v-badge :content="futureReserve(value)" class="ml-2">
                нада
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
            }
        }
    }
</script>

<style scoped>

</style>
