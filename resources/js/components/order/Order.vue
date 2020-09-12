<template>
    <model :aggregate-attributes="['orderLinesCount', 'orderLinesSum', 'cashFlowsSum']"
           :lines="OrderLines"
           :with="['seller', 'employee']"
           date="INVOICE_DATA"
           name="Заказ"
           number="INVOICE_NUM"
           value="order"
    />
</template>

<script>
    import Model from '../Model'
    import OrderLines from "./OrderLines";

    export default {
        name: "Order",
        components: {Model},
        computed: {
            OrderLines() {
                return OrderLines;
            }
        }
    }
</script>

<style scoped>

</style>
