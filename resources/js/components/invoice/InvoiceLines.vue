<template>
    <v-data-table
        :footer-props="{
            showFirstLastPage: true,
        }"
        :headers="headers2"
        :items="items2"
        :loading="loading"
        :multi-sort="true"
        :options.sync="options"
        :server-items-length="total"
        :loading-text="loadingText"
        :single-expand="true"
        item-key="REALPRICECODE"
        show-expand
    >
        <template v-slot:top>
            <invoice-edit :value="value"
                          @input="proxyInput"
                          @reloadInvoice="reloadValue"
                          @newInvoiceLine="newInvoiceLine"
                          :sort-by="options.sortBy"
                          :sort-desc="options.sortDesc"
            />
        </template>
        <template v-slot:header.actions>
            <v-btn icon @click="reloadValue">
                <v-icon>mdi-reload</v-icon>
            </v-btn>
        </template>
        <template v-slot:item.actions="{ item }">
            <v-hover
                v-slot="{ hover }"
                v-if="editable && !item.reservesQuantity && !item.pickUpsQuantity && !item.transferOutLinesQuantity"
            >
                <v-btn icon color="red" @click="remove(item)">
                    <v-icon v-if="hover">mdi-cart-remove</v-icon>
                </v-btn>
            </v-hover>
        </template>
        <template v-slot:item.name.NAME="{ item }">
            <good-name v-model="item" :prim="item.good.PRIM"/>
        </template>
        <template v-slot:item.QUAN="{ item }">
            <edit-field :disabled="!editable"
                        :rules="QUANRules"
                        attribute="QUAN"
                        v-model="item"
                        @save="save"
            />
        </template>
        <template v-slot:item.reservesQuantity="{ item }">
            <reserves-modal :text="item.reservesQuantity"
                            :text-class="reserveClass(item)"
                            :name="item.name.NAME"
                            v-model="item.good"
            />
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
            <edit-field :disabled="!editable"
                        :rules="PRICERules"
                        @save="priceCalculate"
                        attribute="priceWithoutVat"
                        v-model="item"
            >
                <template v-slot:cell>
                    {{ item.priceWithoutVat | formatRub }}
                </template>
            </edit-field>
        </template>
        <template v-slot:item.PRICE="{ item }">
            <edit-field :disabled="!editable"
                        :rules="PRICERules"
                        @save="save"
                        attribute="PRICE"
                        v-model="item"
            >
                <template v-slot:cell>
                    {{ item.PRICE | formatRub }}
                </template>
            </edit-field>
        </template>
        <template v-slot:item.sumWithoutVat="{ item }">
            <edit-field :disabled="!editable"
                        :rules="SUMMAPRules"
                        @save="sumCalculate"
                        attribute="sumWithoutVat"
                        v-model="item"
            >
                <template v-slot:cell>
                    {{ item.sumWithoutVat | formatRub }}
                </template>
            </edit-field>
        </template>
        <template v-slot:item.SUMMAP="{ item }">
            <edit-field :disabled="!editable"
                        :rules="SUMMAPRules"
                        @save="save"
                        attribute="SUMMAP"
                        v-model="item"
            >
                <template v-slot:cell>
                    {{ item.SUMMAP | formatRub }}
                </template>
            </edit-field>
        </template>
        <template v-slot:item.PRIM="{ item }">
            <edit-field :disabled="!editable"
                        :rules="PRIMRules"
                        @save="save"
                        attribute="PRIM"
                        v-model="item"
            />
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
    import tableMixin from "../../mixins/tableMixin";
    import ExpandTransferOutLines from "../ExpandTransferOutLines";
    import TransferOutList from "../transferOut/TransferOutList";
    import InvoiceEdit from "./InvoiceEdit";
    import utilsMixin from "../../mixins/utilsMixin";
    import OrderLineInWay from "../order/OrderLineInWay";
    import {mapGetters} from 'vuex';
    import GoodName from "../good/GoodName";
    import EditField from "../EditField";
    import ReservesModal from "../ReservesModal";

    export default {
        name: "InvoiceLines",
        mixins: [tableMixin, utilsMixin],
        components: {
            ReservesModal,
            EditField, GoodName, OrderLineInWay, InvoiceEdit, TransferOutList, ExpandTransferOutLines},
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
                        'invoice.SCODE',
                    ],
                    filterOperators: ['='],
                    filterValues: [this.value.SCODE],
                    sortBy: ['category.CATEGORY', 'name.NAME'],
                    sortDesc: [false, false],
                    itemsPerPage: -1,
                },
                mobileFiltersVisible: false,
                dependent: true,
                model: 'INVOICE-LINE',
            }
        },
        computed: {
            headers2() {
                return _.filter(this.headers, (v) => !v.notInvoiceLines);
            },
            items2() {
                return _.map(this.items, (v) => {
                    v.priceWithoutVat = v.SUMMAP / (100 + this.vat) * 100 / v.QUAN;
                    v.sumWithoutVat = v.SUMMAP / (100 + this.vat) * 100;
                    return v;
                })
            },
            ...mapGetters({vat: 'VAT'}),
            QUANRules() {
                return [this.rules.isInteger, this.rules.required, this.rules.positive]
            },
            PRICERules() {
                return [this.rules.isNumber, this.rules.required, this.rules.positive]
            },
            SUMMAPRules() {
                return [this.rules.isNumber, this.rules.required, this.rules.positive]
            },
            PRIMRules() {
                return [() => true];
            },
            editable() {
                return this.$store.getters['AUTH/HAS_PERMISSION']('invoice-line.update') && this.value.STATUS === 0;
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
            priceCalculate(item) {
                item.PRICE = item.priceWithoutVat * (100 + this.vat) / 100 || '';
                if (item.PRICE) item.PRICE = item.PRICE.toFixed(2);
                this.save(item);
            },
            sumCalculate(item) {
                item.SUMMAP = item.sumWithoutVat * (100 + this.vat) / 100 || '';
                if (item.SUMMAP) item.SUMMAP = item.SUMMAP.toFixed(2);
                this.save(item);
            },
            newInvoiceLine(id) {
                this.itemIds.push(id);
                this.total += 1;
            },
            async remove(item) {
                try {
                    await this.$store.dispatch('INVOICE-LINE/REMOVE', item.REALPRICECODE);
                    await this.reloadValue();
                } catch (e) {}
            },
            async reloadValue() {
                this.loading = true;
                const payload = {
                    id: this.value.SCODE,
                    query: {
                        with: ['buyer', 'employee', 'firm'],
                        aggregateAttributes: [
                            'invoiceLinesCount', 'invoiceLinesSum', 'cashFlowsSum', 'transferOutLinesSum'
                        ],
                    }
                };
                const newValue = await this.$store.dispatch('INVOICE/GET', payload);
                this.$emit('input', newValue);
                this.loading = false;
            }
         }
    }
</script>

<style scoped>

</style>
