<template>
    <model :aggregate-attributes="['invoiceLinesCount', 'invoiceLinesSum', 'cashFlowsSum', 'transferOutLinesSum']"
           :lines="InvoiceLines"
           :with="['buyer', 'employee', 'firm']"
           date="DATA"
           name="Счет"
           number="NS"
           value="invoice"
    />
</template>

<script>
    import Model from '../Model'
    import InvoiceLines from "./InvoiceLines";

    export default {
        name: "Invoice",
        components: {Model},
        computed: {
            InvoiceLines() {
                return InvoiceLines;
            }
        }
    }
</script>

<style scoped>

</style>
