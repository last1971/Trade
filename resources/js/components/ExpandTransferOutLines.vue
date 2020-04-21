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
        class="elevation-1"
    >
        <template v-slot:top>
            <div class="title ml-2">В отгрузках(УПД)</div>
        </template>
        <template v-slot:item.transferOut.DATA="{ item }">
            {{ item.transferOut.DATA | formatDate }}
        </template>
        <template v-slot:item.transferOut.NSF="{ item }">
            <router-link :to="{ name: 'transfer-out', params: { id: item.transferOut.SFCODE } }">
                {{ item.transferOut.NSF }}
            </router-link>
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
                    page: 1,
                },
                mobileFiltersVisible: false,
                dependent: true,
            }
        },
        computed: {
            headers() {
                return [
                    {text: 'Дата', value: 'transferOut.DATA', sortable: false},
                    {text: 'Номер', value: 'transferOut.NSF', sortable: false},
                    {text: 'Кол.-во', value: 'QUAN', align: 'right'},
                    {text: 'Страна', value: 'STRANA'},
                    {text: 'ГТД', value: 'GTD'},
                ];
            },
            model() {
                return 'TRANSFER-OUT-LINE';
            },
        },
    }
</script>

<style scoped>

</style>
