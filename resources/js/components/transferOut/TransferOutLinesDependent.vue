<template>
    <v-card>
        <v-card-title class="headline">
            <v-btn v-if="invoiceLine" @click="isInvoiceLine = !isInvoiceLine" icon left>
                <v-icon v-if="isInvoiceLine">
                    mdi-chevron-right
                </v-icon>
                <v-icon v-else>
                    mdi-chevron-left
                </v-icon>
            </v-btn>
            <v-spacer/>
            <span class="headline">{{ title }}</span>
            <v-spacer/>
        </v-card-title>
        <v-divider></v-divider>
        <v-data-table
            :headers="headers"
            :items="items"
            :loading="loading"
            :options.sync="options"
            :server-items-length="total"
            :hide-default-footer="isInvoiceLine"
            item-key="REALPRICEFCODE"
            :loading-text="loadingText"
            class="mx-2"
        >
            <template v-slot:item.transferOut.DATA="{ item }">
                {{ item.transferOut.DATA | formatDate }}
            </template>
            <template v-slot:item.transferOut.NSF="{ item }">
                <router-link :to="{ name: 'transfer-out', params: { id: item.transferOut.SFCODE } }">
                    {{ item.transferOut.NSF }}
                </router-link>
            </template>
            <template v-slot:item.transferOut.invoice.DATA="{ item }">
                {{ item.transferOut.invoice.DATA | formatDate }}
            </template>
            <template v-slot:item.transferOut.invoice.NS="{ item }">
                <router-link :to="{ name: 'invoice', params: { id: item.transferOut.SCODE } }">
                    {{ item.transferOut.invoice.NS }}
                </router-link>
            </template>
            <template v-slot:item.PRICE="{ item }">
                {{ item.PRICE | formatRub }}
            </template>
            <template v-slot:item.SUMMAP="{ item }">
                {{ item.SUMMAP | formatRub }}
            </template>
        </v-data-table>
    </v-card>
</template>

<script>
import tableMixin from "../../mixins/tableMixin";

export default {
    name: "TransferOutLinesDependent",
    mixins: [tableMixin],
    props: {
        invoiceLine: {
            type: Object,
        },
        good: {
            type: Object,
            required: true,
        },
        text: {
            type: [String, Number],
            default: 0,
        },
        textClass: {
            type: String,
            default: ''
        },
    },
    data() {
        return {
            isInvoiceLine: !!this.invoiceLine,
            mobileFiltersVisible: false,
            dependent: true,
            options: this.invoiceLine
                ? {
                    with: ['transferOut.invoice'],
                    filterAttributes: [
                        'REALPRICECODE',
                    ],
                    filterOperators: ['='],
                    filterValues: [this.invoiceLine.REALPRICECODE],
                    sortBy: ['REALPRICEFCODE'],
                    sortDesc: [true],
                    itemsPerPage: -1,
                    page: 1,
                }
                : {
                    with: ['transferOut.invoice', 'transferOut.buyer'],
                    filterAttributes: [
                        'REALPRICEF.GOODSCODE',
                    ],
                    filterOperators: ['='],
                    filterValues: [this.good.GOODSCODE],
                    sortBy: ['REALPRICEFCODE'],
                    sortDesc: [true],
                    itemsPerPage: 15,
                    page: 1,
                }
        }
    },
    computed: {
        headers() {
            return this.isInvoiceLine
                ? [
                    {text: 'Дата УПД', value: 'transferOut.DATA', sortable: false},
                    {text: 'Номер УПД', value: 'transferOut.NSF', sortable: false},
                    {text: 'Кол.-во', value: 'QUAN', align: 'right'},
                    {text: 'Страна', value: 'STRANA'},
                    {text: 'ГТД', value: 'GTD'},
                ]
                : [
                    {text: 'Дата УПД', value: 'transferOut.DATA', sortable: false},
                    {text: 'Номер УПД', value: 'transferOut.NSF', sortable: false},
                    {text: 'Дата Счета', value: 'transferOut.invoice.DATA', sortable: false},
                    {text: 'Номер Счета', value: 'transferOut.invoice.NS', sortable: false},
                    {text: 'Покупатель', value: 'transferOut.buyer.SHORTNAME', sortable: false},
                    {text: 'Кол.-во', value: 'QUAN', align: 'right'},
                    {text: 'Цена с НДС', value: 'PRICE', align: 'right'},
                    {text: 'Сумма с НДС', value: 'SUMMAP', align: 'right'},
                    {text: 'Страна', value: 'STRANA'},
                    {text: 'ГТД', value: 'GTD'},
                ];
        },
        model() {
            return 'TRANSFER-OUT-LINE';
        },
        name() {
            return this.invoiceLine ?  this.invoiceLine.name.NAME : this.good.name.NAME;
        },
        invoice() {
            if (this.invoiceLine.invoice) return this.invoiceLine.invoice;
            const invoice = this.$store.getters['INVOICE/GET'](this.invoiceLine.SCODE)
            if (!invoice) this.$store.dispatch('INVOICE/CACHE', this.invoiceLine.SCODE);
            return invoice || {
                NS: '?',
                DATA: new Date()
            }
        },
        title() {
            let ret = 'Исходящие УПД для ' + this.name;
            return this.isInvoiceLine
                ? ret + ' из Счета № ' + this.invoice.NS + ' от '
                + this.$options.filters.formatDate(this.invoice.DATA)
                : ret;
        },
    },
    watch: {
        isInvoiceLine(v) {
            if (v) {
                this.options = {
                    with: ['transferOut.invoice'],
                    filterAttributes: [
                        'REALPRICECODE',
                    ],
                    filterOperators: ['='],
                    filterValues: [this.invoiceLine.REALPRICECODE],
                    sortBy: ['REALPRICEFCODE'],
                    sortDesc: [true],
                    itemsPerPage: -1,
                    page: 1,
                };
            }
            else {
                this.options = {
                    with: ['transferOut.invoice', 'transferOut.buyer'],
                    filterAttributes: [
                        'REALPRICEF.GOODSCODE',
                    ],
                    filterOperators: ['='],
                    filterValues: [this.good.GOODSCODE],
                    sortBy: ['REALPRICEFCODE'],
                    sortDesc: [true],
                    itemsPerPage: 15,
                    page: 1,
                };
            }
        }
    },
}
</script>

<style scoped>

</style>
