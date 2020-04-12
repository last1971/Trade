<template>
    <div>
        <transfer-out-lines :transfer-out="transferOut" v-if="transferOut"/>
    </div>
</template>

<script>
    import TransferOutLines from "./TransferOutLines";

    export default {
        name: "TransferOut",
        components: {TransferOutLines},
        data() {
            return {
                previousValue: null,
                with: ['buyer', 'employee', 'firm', 'invoice'],
            }
        },
        computed: {
            transferOut() {
                if (this.$route.name !== 'transfer-out') return this.previousValue;
                const transferOut =
                    this.$route.params.id ? this.$store.getters['TRANSFER-OUT/GET'](this.$route.params.id) : null;
                if (!transferOut || !this.with.reduce((sum, v) => sum && transferOut[v], true)) {
                    this.getTransferOut();
                } else {
                    this.previousValue = transferOut;
                    this.$store.commit('BREADCRUMBS/PUT', {
                        text: `Исх.УПД № ${this.previousValue.NSF} от
                            ${this.$options.filters.formatDate(this.previousValue.DATA)}`,
                        to: {name: 'transfer-out', params: {id: this.previousValue.SFCODE}},
                        disabled: true,
                    });
                }
                return this.previousValue;
            }
        },
        methods: {
            getTransferOut() {
                if (this.$route.params.id)
                    this.$store.dispatch(
                        'TRANSFER-OUT/GET',
                        {
                            id: this.$route.params.id,
                            query: {
                                with: this.with,
                                aggregateAttributes: [
                                    'transferOutLinesCount', 'transferOutLinesSum'
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
