<template>
    <v-data-table
        :footer-props="{
            showFirstLastPage: true,
        }"
        :headers="headers"
        :items="items"
        :loading="loading"
        :multi-sort="true"
        :options.sync="options"
        :server-items-length="total"
        loading-text="Loading... Please wait"
    >
        <template v-slot:item.PRICE="{ item }">
            {{ item.PRICE | formatRub }}
        </template>
        <template v-slot:item.SUMMAP="{ item }">
            {{ item.SUMMAP | formatRub }}
        </template>
    </v-data-table>
</template>

<script>
    import tableMixin from "../mixins/tableMixin";

    export default {
        name: "InvoiceLine",
        props: {
            invoice: {
                type: Object,
                required: true,
            }
        },
        mixins: [tableMixin],
        data() {
            return {
                options: {
                    with: ['good.category', 'name'],
                    aggregateAttributes: [
                        'reservesQuantity', 'pickUpsQuantity', 'transferOutLinesQuantity'
                    ],
                    filterAttributes: [
                        'SCODE',
                        'name.NAME',
                    ],
                    filterOperators: ['=', 'CONTAIN',],
                    filterValues: [this.invoice.SCODE, ''],
                },
                rules: {
                    isInteger: n => _.isInteger(_.toNumber(n)) || 'Введите целое число',
                    isNumber: n => !_.isNaN(_.toNumber(n)) || 'Введите число',
                    required: v => (v === 0 || !!v) || 'Обязателный'
                },
                mobileFiltersVisible: false,
                dependent: true,
            }
        },
    }
</script>

<style scoped>

</style>
