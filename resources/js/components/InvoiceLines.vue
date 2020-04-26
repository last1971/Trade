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
        loading-text="Loading... Please wait"
        :single-expand="true"
        item-key="REALPRICECODE"
        show-expand
    >
        <template v-slot:top>
            <invoice-edit :value="value"/>
        </template>
        <template v-slot:item.name.NAME="{ item }">
            <v-tooltip top>
                <template v-slot:activator="{ on }">
                    <span v-on="on">{{ item.name.NAME }}</span>
                </template>
                <span>{{ item.good.PRIM.trim() || 'описания нет' }}</span>
            </v-tooltip>
        </template>
        <template v-slot:item.QUAN="{ item }">
            <v-edit-dialog @save="save(item, 'QUAN')">
                {{ item.QUAN }}
                <template v-if="editable" v-slot:input>
                    <v-text-field
                        :rules="QUANRules"
                        single-line
                        v-model="item.QUAN"
                    />
                </template>
            </v-edit-dialog>
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
            <v-edit-dialog @save="save(item, 'PRICE')">
                {{ item.priceWithoutVat | formatRub }}
                <template v-if="editable" v-slot:input>
                    <v-text-field
                        :rules="PRICERules"
                        @input="priceCalculate(item)"
                        single-line
                        v-model="item.priceWithoutVat"
                    />
                </template>
            </v-edit-dialog>
        </template>
        <template v-slot:item.PRICE="{ item }">
            <v-edit-dialog @save="save(item, 'PRICE')">
                {{ item.PRICE | formatRub }}
                <template v-if="editable" v-slot:input>
                    <v-text-field
                        :rules="PRICERules"
                        single-line
                        v-model="item.PRICE"
                    />
                </template>
            </v-edit-dialog>
        </template>
        <template v-slot:item.sumWithoutVat="{ item }">
            <v-edit-dialog @save="save(item, 'SUMMAP')">
                {{ item.sumWithoutVat | formatRub }}
                <template v-if="editable" v-slot:input>
                    <v-text-field
                        :rules="SUMMAPRules"
                        @change="sumCalculate(item)"
                        single-line
                        v-model="item.sumWithoutVat"
                    />
                </template>
            </v-edit-dialog>
        </template>
        <template v-slot:item.SUMMAP="{ item }">
            <v-edit-dialog @save="save(item, 'SUMMAP')">
                {{ item.SUMMAP | formatRub }}
                <template v-if="editable" v-slot:input>
                    <v-text-field
                        :rules="PRICERules"
                        single-line
                        v-model="item.SUMMAP"
                    />
                </template>
            </v-edit-dialog>
        </template>
        <template v-slot:item.PRIM="{ item }">
            <v-edit-dialog @save="save(item, 'PRIM')">
                {{ item.PRIM }}
                <template v-if="editable" v-slot:input>
                    <v-text-field
                        :rules="PRIMRules"
                        single-line
                        v-model="item.PRIM"
                    />
                </template>
            </v-edit-dialog>
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
    import {mapGetters} from 'vuex';

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
                        'invoice.SCODE',
                    ],
                    filterOperators: ['='],
                    filterValues: [this.value.SCODE],
                    sortBy: ['category.CATEGORY', 'name.NAME'],
                    sortDesc: [false, false],
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
                return [this.rules.isInteger, this.rules.required]
            },
            PRICERules() {
                return [this.rules.isNumber, this.rules.required]
            },
            SUMMAPRules() {
                return [this.rules.isNumber, this.rules.required]
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
            save(item, attr) {
                const validate = this[attr + 'Rules'].reduce((res, f) => res && f(item[attr]) === true, true);
                if (validate) {
                    this.$store.dispatch(this.model + '/UPDATE', {item, options: this.options})
                        .then((response) => {
                            const index = _.findIndex(this.items, {REALPRICECODE: item.REALPRICECODE});
                            this.items.splice(index, 1, response.data);
                        })
                        .catch(() => this.restore(item));
                } else {
                    const error = this[attr + 'Rules'].reduce((res, f) =>
                        res === true ? f(item[attr]) : res, true
                    );
                    this.restore(item);
                    this.$store.commit('SNACKBAR/ERROR', error);
                }
            },
            restore(item) {
                const restore = this.$store.getters[this.model + '/GET'](item.REALPRICECODE);
                const index = _.findIndex(this.items, {REALPRICECODE: item.REALPRICECODE});
                this.items.splice(index, 1, restore);
            },
            priceCalculate(item) {
                item.PRICE = item.priceWithoutVat * (100 + this.vat) / 100 || '';
            },
            sumCalculate(item) {
                item.SUMMAP = item.sumWithoutVat * (100 + this.vat) / 100 || '';
                if (item.SUMMAP) item.SUMMAP = item.SUMMAP.toFixed(2);
            }
        }
    }
</script>

<style scoped>

</style>
