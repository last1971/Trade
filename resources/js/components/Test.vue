<template>
    <invoice-select v-model="invoice"/>
</template>

<script>
    import InvoiceSelect from "./invoice/InvoiceSelect";
    export default {
        name: "Test",
        components: {InvoiceSelect},
        data() {
            return {
                invoice: null,
            }
        },
        computed: {

        },
        methods: {

        }
    }
</script>

<style scoped>

</style>
