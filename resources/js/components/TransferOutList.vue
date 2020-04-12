<template>
    <v-row class="m-2">
        <v-card :key="transferOut.SFCODE"
                :to="{ name: 'transfer-out', params: { id: transferOut.SFCODE } }"
                class="m-2"
                link
                raised
                v-for="transferOut in transferOuts"
        >
            <v-card-title>
                УПД № {{ transferOut.NSF }} от {{ transferOut.DATA | formatDate }}
            </v-card-title>
            <v-card-text> {{ transferOut.transferOutLinesCount }} строк(и)
                на сумму {{ transferOut.transferOutLinesSum | formatRub }}
            </v-card-text>
        </v-card>
    </v-row>
</template>

<script>
    export default {
        name: "TransferOutList",
        props: {
            invoice: {
                type: Object,
                required: true,
            },
        },
        data() {
            return {
                SCODE: null
            }
        },
        computed: {
            transferOuts() {
                const transferOuts = this.$store.getters['TRANSFER-OUT/ALL'];
                return _.filter(transferOuts, {SCODE: this.invoice.SCODE});
            }
        },
        watch: {
            invoice() {
                if (this.invoice.SCODE !== this.SCODE) this.getTransferOuts();
            }
        },
        created() {
            this.getTransferOuts();
        },
        methods: {
            getTransferOuts() {
                this.SCODE = this.invoice.SCODE;
                this.$store.dispatch('TRANSFER-OUT/ALL', {
                    aggregateAttributes: ['transferOutLinesSum', 'transferOutLinesCount'],
                    filterAttributes: [
                        'SCODE',
                    ],
                    filterOperators: ['='],
                    filterValues: [this.invoice.SCODE],
                    itemsPerPage: -1,
                })
            }
        }
    }
</script>

<style scoped>

</style>
