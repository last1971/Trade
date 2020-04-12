<template>
    <div v-if="invoice">
        <invoice-lines :key="invoice.SCODE" :value="invoice"/>
    </div>
</template>

<script>
    import InvoiceLines from "./InvoiceLines";

    export default {
        name: "Invoice",
        components: {InvoiceLines},
        data() {
            return {
                previousValue: null
            }
        },
        computed: {
            invoice() {
                if (this.$route.name !== 'invoice') return this.previousValue;
                this.previousValue =
                    this.$route.params.id ? this.$store.getters['INVOICE/GET'](this.$route.params.id) : null;
                if (!this.previousValue) {
                    this.getInvoice();
                } else {
                    this.$store.commit('BREADCRUMBS/PUT', {
                        text: `Счет № ${this.previousValue.NS} от
                            ${this.$options.filters.formatDate(this.previousValue.DATA)}`,
                        to: {name: 'invoice', params: {id: this.previousValue.SCODE}},
                        disabled: true,
                    });
                }
                return this.previousValue;
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
