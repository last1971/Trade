<template>
    <v-data-table
        :headers="headers"
        :items="items"
        :loading="loading"
        :options.sync="options"
        :server-items-length="total"
        hide-default-footer
        item-key="REALPRICEFCODE"
        loading-text="Loading... Please wait"
    >
        <template v-slot:item.transferOut.DATA="{ item }">
            {{ item.transferOut.DATA | formatDate }}
        </template>
    </v-data-table>
</template>

<script>
    import tableMixin from "../mixins/tableMixin";

    export default {
        name: "ExpandTransferOutLines",
        props: {
            invoiceLine: {
                type: Object,
                required: true,
            }
        },
        mixins: [tableMixin],
        data() {
            return {
                options: {
                    with: ['transferOut'],
                    filterAttributes: [
                        'REALPRICECODE',
                    ],
                    filterOperators: ['='],
                    filterValues: [this.invoiceLine.REALPRICECODE],
                    itemsPerPage: -1,
                },
                mobileFiltersVisible: false,
                dependent: true,
            }
        },
        computed: {
            headers() {
                return [
                    {text: 'Дата', value: 'transferOut.DATA'},
                    {text: 'Номер', value: 'transferOut.NSF'},
                    {text: 'Кол.-во', value: 'QUAN'},
                    {text: 'Страна', value: 'STRANA'},
                    {text: 'ГТД', value: 'GTD'},
                ];
            },
            model() {
                return 'TRANSFER-OUT-LINE';
            },
        }
    }
</script>

<style scoped>

</style>
