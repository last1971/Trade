<template>
    <v-container>
        <v-row>
            <v-col cols="12">
                {{ invoice.NS }}
                от
                {{ invoice.DATA | formatDate }}
                для
                {{ invoice.buyer.SHORTNAME }}
                на
                {{ invoice.invoiceLinesSum | formatRub }}
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
export default {
    name: "InvoiceInLine",
    props: {
        invoice: {
            type: Object,
        }
    }
}
</script>

<style scoped>

</style>
