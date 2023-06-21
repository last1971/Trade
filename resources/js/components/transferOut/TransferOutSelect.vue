<template>
    <v-select
        :items="transferOuts"
        @change="change" label="УПД"
        v-model="proxy"
        item-value="SFCODE"
        clearable
    >
        <template v-slot:item="{ item }">
            № {{ item.NSF }} от {{ item.DATA | formatDate }} на сумму {{ item.transferOutLinesSum | formatRub }}
        </template>
        <template v-slot:selection="{ item }">
                № {{ item.NSF }} от {{ item.DATA | formatDate }}
        </template>
    </v-select>
</template>

<script>
    import utilsMixin from "../../mixins/utilsMixin";
    import _ from "lodash";

    export default {
        name: 'TransferOutSelect',
        mixins: [utilsMixin],
        props: {
            invoice: {
                type: Object,
                required: true,
            },
            value: {
                type: Number,
                required: false,
            }
        },
        async mounted() {
            await this.load();
        },
        watch: {
            async invoice() {
                await this.load();
            }
        },
        data() {
            return {
                transferOuts: [],
            }
        },
        methods: {
            change(payload) {
                const to = _.find(this.transferOuts, { SFCODE: payload })
                this.$emit('change', to)
            },
            async load() {
                const response = await this.$store.dispatch(
                    'TRANSFER-OUT/ALL',
                    {
                        itemsPerPage: -1,
                        filterAttributes: ['SCODE'],
                        filterOperators: ['='],
                        filterValues: [this.invoice.SCODE],
                        aggregateAttributes: [
                           'transferOutLinesSum'
                        ],
                    }
                );
                this.transferOuts = response.copyItems;
            }
        }
    }
</script>

<style scoped>

</style>
