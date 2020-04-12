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
        :single-expand="true"
        item-key="REALPRICECODE"
        show-expand
    >
        <template v-slot:top>
            <invoice-edit :value="value"/>
        </template>
        <template v-slot:item.reservesQuantity="{ item }">
            <div :class="reserveClass(item)">
                {{ item.reservesQuantity }}
            </div>
        </template>
        <template v-slot:item.pickUpsQuantity="{ item }">
            <div :class="pickUpClass(item)">
                {{ item.pickUpsQuantity }}
            </div>
        </template>
        <template v-slot:item.transferOutLinesQuantity="{ item }">
            <div :class="transferOutClass(item)">
                {{ item.transferOutLinesQuantity }}
            </div>
        </template>
        <template v-slot:item.PRICE="{ item }">
            {{ item.PRICE | formatRub }}
        </template>
        <template v-slot:item.SUMMAP="{ item }">
            {{ item.SUMMAP | formatRub }}
        </template>
        <template v-slot:expanded-item="{ headers, item }">
            <td :colspan="headers.length" :key="item.REALPRICECODE">
                <expand-transfer-out-lines :invoice-line="item" class="my-2"/>
                <order-line-in-way :invoice-line="item" class="my-2"/>
            </td>
        </template>
        <template v-slot:footer>
            <transfer-out-list :invoice="value"/>
        </template>
    </v-data-table>
</template>

<script>
    import tableMixin from "../mixins/tableMixin";
    import ExpandTransferOutLines from "./ExpandTransferOutLines";
    import TransferOutList from "./TransferOutList";
    import InvoiceEdit from "./InvoiceEdit";
    import utilsMixin from "../mixins/utilsMixin";
    import OrderLineInWay from "./OrderLineInWay";

    export default {
        name: "InvoiceLines",
        mixins: [tableMixin, utilsMixin],
        components: {OrderLineInWay, InvoiceEdit, TransferOutList, ExpandTransferOutLines},
        props: {
            value: {
                type: Object,
                required: true,
            }
        },
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
                    filterValues: [this.value.SCODE],
                },
                mobileFiltersVisible: false,
                dependent: true,
            }
        },
        methods: {
            reserveClass({QUAN, reservesQuantity, pickUpsQuantity, transferOutLinesQuantity}) {
                if (QUAN === transferOutLinesQuantity) return;
                if (QUAN === pickUpsQuantity + reservesQuantity) return 'success--text';
                if (reservesQuantity === 0) return 'red--text';
                return 'primary--text';
            },
            pickUpClass({QUAN, pickUpsQuantity, transferOutLinesQuantity}) {
                if (pickUpsQuantity === 0) return 'red--text';
                if (QUAN === transferOutLinesQuantity) return 'success--text';
                return 'primary--text';
            },
            transferOutClass({QUAN, transferOutLinesQuantity}) {
                if (transferOutLinesQuantity === 0) return 'red--text';
                if (QUAN === transferOutLinesQuantity) return 'success--text';
                return 'primary--text';
            }

        }
    }
</script>

<style scoped>

</style>
