<template>
    <v-dialog v-model="isActiveProxy">
        <template v-if="withActivator" v-slot:activator="{ on }">
            <v-btn v-on="on"
                   :icon="icon"
                   :x-small="xSmall"
                   :plain="plain"
                   :rounded="rounded"
                   :class="textClass"
                   :disabled="notCanCashFlow"
            >
                <slot name="button">
                    {{ text | formatRub }}
                </slot>
            </v-btn>
        </template>
        <v-card>
            <v-card-title class="headline">
                <span class="headline">{{ title }}</span>
                <v-spacer/>
                <span class="headline">{{ amountText }}</span>
                <v-spacer/>
                <cash-flow-add v-model="value" @updated="reloadValue(true)" :disabled="addPossible"/>
                <v-spacer/>
                <v-btn @click="isActiveProxy = false" icon right>
                    <v-icon color="red">
                        mdi-close
                    </v-icon>
                </v-btn>
            </v-card-title>
            <v-divider></v-divider>
            <v-data-table
                :headers="headers"
                :items="items"
                :loading="loading"
                :multi-sort="true"
                :options.sync="options"
                :server-items-length="total"
                :item-key="itemKey"
                :loading-text="loadingText"
            >
                <template v-slot:item.actions="{ item }">
                    <v-hover
                        v-slot="{ hover }"
                    >
                        <v-btn icon color="red" @click="remove(item)">
                            <v-icon v-if="hover">mdi-cart-remove</v-icon>
                        </v-btn>
                    </v-hover>
                </template>
                <template v-slot:item.DATA="{ item }">
                    {{ item.DATA | formatDate }}
                </template>
                <template v-slot:item.MONEYSCHET="{ item }">
                    {{ item.MONEYSCHET | formatRub }}
                </template>
            </v-data-table>
        </v-card>
    </v-dialog>
</template>

<script>
    import tableMixin from "../mixins/tableMixin";
    import utilsMixin from "../mixins/utilsMixin";
    import tableOptionsRouteMixin from "../mixins/tableOptionsRouteMixin";
    import CashFlowAdd from "./CashFlowAdd.vue";

    export default {
        name: 'CashFlowsModal',
        components: {CashFlowAdd},
        mixins: [tableMixin, utilsMixin, tableOptionsRouteMixin],
        props: {
            value: {
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
            icon: {
                type: Boolean,
                default: false,
            },
            xSmall: {
                type: Boolean,
                default: false,
            },
            plain: {
                type: Boolean,
                default: false,
            },
            rounded: {
                type: Boolean,
                default: false,
            },
            withActivator: {
                type: Boolean,
                default: true,
            },
            isActive: {
                type: Boolean,
                default: false,
            }
        },
        data() {
            return {
                options: {
                    with: ['transferOut'],
                    filterAttributes: [
                        'SCHET.SCODE',
                    ],
                    filterOperators: ['='],
                    filterValues: [this.value.SCODE],
                    itemsPerPage: -1,
                },
                datePicker: false,
                mobileFiltersVisible: false,
                model: 'CASH-FLOW',
                itemKey: 'SCHETCODE',
                isActiveProxy: this.isActive,
                dependent: true,
            }
        },
        computed: {
            notCanCashFlow() {
                return !this.$store.getters['AUTH/HAS_PERMISSION']('cash-flow.index');
            },
            title() {
                return 'Оплаты для Счета № ' + this.value.NS + ' от '
                    + this.$options.filters.formatDate(this.value.DATA);
            },
            amount() {
                return this.items.reduce((s, v) => s + parseFloat(v.MONEYSCHET), 0);
            },
            amountText() {
                return this.amount >0 ? ' Оплачено ' + this.$options.filters.formatRub(this.amount) : '';
            },
            addPossible() {
                return this.amount === parseFloat(this.value.invoiceLinesSum);
            }
        },
        methods: {
            async remove(item) {
                try {
                    await this.$store.dispatch('CASH-FLOW/REMOVE', item.SCHETCODE);
                    await this.reloadValue();
                } catch (e) {
                }
            },
            async reloadValue(reloadLines = false) {
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
                if (reloadLines) await this.updateItems(false);
                this.loading = false;
            },
        }
    }

</script>

<style scoped>

</style>
