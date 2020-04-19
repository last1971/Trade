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
        :single-expand="true"
        item-key="REALPRICECODE"
        loading-text="Loading... Please wait"
        show-expand
    >
        <template v-slot:top>
            <slot name="top"/>
        </template>
        <template v-slot:item.name.NAME="{ item }">
            <v-tooltip top>
                <template v-slot:activator="{ on }">
                    <span v-on="on">{{ item.name.NAME }}</span>
                </template>
                <span>{{ item.good.PRIM.trim() || 'описания нет' }}</span>
            </v-tooltip>
        </template>
        <template v-slot:item.invoice="{ item }">
            <router-link :to="{ name: 'invoice', params: { id: item.invoice.SCODE } }">
                № {{ item.invoice.NS }} от {{ item.invoice.DATA | formatDate }}
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
    </v-data-table>
</template>

<script>
    import tableMixin from "../mixins/tableMixin";
    import utilsMixin from "../mixins/utilsMixin";
    import OrderLineInWay from "./OrderLineInWay";
    import ExpandTransferOutLines from "./ExpandTransferOutLines";

    export default {
        name: "InvoiceLinesDependent",
        mixins: [tableMixin, utilsMixin],
        components: {OrderLineInWay, ExpandTransferOutLines},
        props: {
            value: {
                type: Object,
                required: true,
            }
        },
        data() {
            return {
                mobileFiltersVisible: false,
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
