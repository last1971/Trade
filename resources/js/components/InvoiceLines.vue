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
        :single-expand="false"
        item-key="REALPRICECODE"
        show-expand
    >
        <template v-slot:item.PRICE="{ item }">
            {{ item.PRICE | formatRub }}
        </template>
        <template v-slot:item.SUMMAP="{ item }">
            {{ item.SUMMAP | formatRub }}
        </template>
        <template v-slot:expanded-item="{ headers, item }">
            <td :colspan="headers.length">
                <expand-transfer-out-lines :invoice-line="item" class="my-2"/>
            </td>
        </template>
        <template v-slot:footer>
            <transfer-out-list :invoice="invoice"/>
        </template>
    </v-data-table>
</template>

<script>
    import tableMixin from "../mixins/tableMixin";
    import ExpandTransferOutLines from "./ExpandTransferOutLines";
    import TransferOutList from "./TransferOutList";

    export default {
        name: "InvoiceLines",
        components: {TransferOutList, ExpandTransferOutLines},
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
                    with: ['category', 'good', 'name'],
                    aggregateAttributes: [
                        'reservesQuantity', 'pickUpsQuantity', 'transferOutLinesQuantity'
                    ],
                    filterAttributes: [
                        'SCODE',
                    ],
                    filterOperators: ['='],
                    filterValues: [this.invoice.SCODE],
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
