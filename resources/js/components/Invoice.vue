<template>
    <div v-if="invoice">
        <invoice-lines :invoice="invoice"/>
    </div>
</template>

<script>
    import InvoiceLines from "./InvoiceLines";

    export default {
        name: "Invoice",
        components: {InvoiceLines},
        computed: {
            invoice() {
                const document =
                    this.$route.params.id ? this.$store.getters['INVOICE/GET'](this.$route.params.id) : null;
                if (!document) {
                    this.getInvoice();
                } else {
                    this.$store.commit('BREADCRUMBS/PUT', {
                        text: `Счет № ${document.NS} от ${this.$options.filters.formatDate(document.DATA)}`,
                        to: {name: 'invoices'},
                        link: true,
                        disabled: true,
                    });
                }
                return document;
            }
        },
        methods: {
            getInvoice() {
                if (this.$route.params.id)
                    this.$store.dispatch(
                        'INVOICE/CACHE',
                        {
                            id: this.$route.params.id,
                            query: {
                                with: ['buyer', 'employee', 'firm'],
                                aggregateAttributes: [
                                    'invoiceLinesCount', 'invoiceLinesSum', 'cashFlowsSum', 'transferOutLinesSum'
                                ],
                            }
                        }
                    );
            }
        },
    }
</script>

<style scoped>

</style>
