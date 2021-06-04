<template>
    <v-card class="m-1">
        <v-card-title class="text-center">
            <invoice-select v-model="currentInvoice" />
        </v-card-title>
    </v-card>
</template>

<script>
import InvoiceSelect from "./InvoiceSelect";
export default {
    name: "InvoiceCard",
    components: {InvoiceSelect},
    computed: {
        currentInvoice: {
            get() {
                return this.$store.getters['INVOICE/GET-CURRENT'];
            },
            set(currentInvoice) {
                this.$store.commit('INVOICE/SET-CURRENT', currentInvoice);
            }
        }
    }
}
</script>

<style scoped>

</style>
