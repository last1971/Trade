<template>
    <div>
        {{ transferOut }}
    </div>
</template>

<script>
    export default {
        name: "TransferOut",
        data() {
            return {
                previousValue: null
            }
        },
        computed: {
            transferOut() {
                if (this.$route.name !== 'transfer-out') return this.previousValue;
                this.previousValue =
                    this.$route.params.id ? this.$store.getters['TRANSFER-OUT/GET'](this.$route.params.id) : null;
                if (!this.previousValue) {
                    this.getTransferOut();
                } else {
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
                        'TRANSFER-OUT/CACHE',
                        {
                            id: this.$route.params.id,
                            query: {
                                with: ['buyer', 'employee', 'firm', 'invoice'],
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
