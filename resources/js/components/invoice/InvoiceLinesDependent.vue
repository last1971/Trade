<template>
    <v-data-table
        :footer-props="{
            showFirstLastPage: true,
        }"
        :headers="mutatedHeaders"
        :items="items"
        :loading="loading"
        :multi-sort="true"
        :options.sync="options"
        :server-items-length="total"
        :single-expand="true"
        :hide-default-footer="hideDefaultFooter"
        item-key="REALPRICECODE"
        loading-text="Loading... Please wait"
        show-expand
    >
        <template v-slot:top>
            <slot name="top"/>
        </template>
        <template v-slot:item.name.NAME="{ item }">
            <good-name v-model="item" :prim="item.good.PRIM"/>
        </template>
        <template v-slot:item.invoice.DATA="{ item }">
            {{ item.invoice.DATA | formatDate }}
        </template>
        <template v-slot:item.invoice.NS="{ item }">
            <router-link :to="{ name: 'invoice', params: { id: item.invoice.SCODE } }">
                {{ item.invoice.NS }}
            </router-link>
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
        <template v-slot:item.priceWithoutVat="{ item }">
            {{ item.SUMMAP / (100 + vat(item)) * 100 / item.QUAN | formatRub }}
        </template>
        <template v-slot:item.PRICE="{ item }">
            {{ item.PRICE | formatRub }}
        </template>
        <template v-slot:item.sumWithoutVat="{ item }">
            {{ item.SUMMAP / (100 + vat(item)) * 100 | formatRub }}
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
    </v-data-table>
</template>

<script>
    import tableMixin from "../../mixins/tableMixin";
    import utilsMixin from "../../mixins/utilsMixin";
    import OrderLineInWay from "../order/OrderLineInWay";
    import ExpandTransferOutLines from "../ExpandTransferOutLines";
    import GoodName from "../good/GoodName";
    import getVAT from "../../helpers/vatHelper";

    export default {
        name: "InvoiceLinesDependent",
        mixins: [tableMixin, utilsMixin],
        components: {OrderLineInWay, ExpandTransferOutLines, GoodName},
        props: {
            value: {
                type: Object,
                required: true,
            },
            hideDefaultFooter: {
                type: Boolean,
                default: false,
            },
            dependentValue: {
                type: Boolean,
                default: false,
            },
            removeHeaders: {
                type: Array,
                default: () => [],
            }
        },
        data() {
            return {
                mobileFiltersVisible: false,
                model: 'INVOICE-LINE',
                dependent: this.dependentValue,
            }
        },
        computed: {
            options: {
                get() {
                    return this.value;
                },
                set(val) {
                    this.$emit('input', val);
                }
            },
            mutatedHeaders() {
                return this.headers.filter(
                    (header) => this.removeHeaders.find((rh) => rh === header.value) === undefined
                );

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
            },
            vat(item) {
                return getVAT(item.invoice.DATA);
            }

        }
    }
</script>

<style scoped>

</style>
