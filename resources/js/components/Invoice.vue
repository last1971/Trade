<template>
    <div v-if="invoice">
        <invoice-line :invoice="invoice"/>
    </div>
</template>

<script>
    import InvoiceLine from "./InvoiceLine";

    export default {
        name: "Invoice",
        components: {InvoiceLine},
        computed: {
            invoice() {
                const document =
                    this.$route.params.id ? this.$store.getters['INVOICE/GET'](this.$route.params.id) : null;
                if (!document) this.getInvoice();
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
        beforeRouteEnter(to, from, next) {
            next(vm => {
                // vm.getInvoice();
            })
        }
    }
</script>

<style scoped>

</style>
