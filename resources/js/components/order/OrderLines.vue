<template>
    <div>
        <v-data-table
            :footer-props="{
            showFirstLastPage: true,
        }"
            :headers="headers"
            :items="items2"
            :loading="loading"
            :multi-sort="true"
            :options.sync="options"
            :server-items-length="total"
            item-key="ID"
            :loading-text="loadingText"
            v-if="isOrderImportLinesEmpty"
        >
            <template v-slot:top>
                <order-edit :value="value"
                            @import="importOpen"
                            @input="proxyInput"
                            @reloadOrder="reloadOrder"
                            @newOrderLine="newOrderLine"
                />
            </template>
            <template v-slot:item.actions="{ item }">
                <v-hover v-slot="{ hover }" v-if="editable && !item.shopLinesQuantity && !item.storeLinesQuantity">
                    <v-btn icon color="red" @click="remove(item)">
                        <v-icon v-if="hover">mdi-cart-remove</v-icon>
                    </v-btn>
                </v-hover>
            </template>
            <template v-slot:item.name.NAME="{ item }">
                <good-name :prim="item.good.PRIM" v-model="item"/>
            </template>
            <template v-slot:item.DATA_PRIH="{ item }">
                {{ (item.DATA_PRIH || value.DATA_PRIH) | formatDate }}
            </template>
            <template v-slot:item.QUAN="{ item }">
                <edit-field :disabled="!editable"
                            :rules="[rules.isInteger, rules.required, rules.positive]"
                            @save="save"
                            attribute="QUAN"
                            v-model="item"
                />
            </template>
            <template v-slot:item.inQuantity="{ item }">
                <div :class="inQuantityColor(item)">
                    {{ item.shopLinesQuantity + item.storeLinesQuantity }}
                </div>
            </template>
            <template v-slot:item.PRICE="{ item }">
                <edit-field :disabled="!editable"
                            :rules="[rules.isNumber, rules.required, rules.positive]"
                            @save="save"
                            attribute="PRICE"
                            v-model="item"
                >
                    <template v-slot:cell>
                        {{ item.PRICE | formatRub }}
                    </template>
                </edit-field>
            </template>
            <template v-slot:item.priceWithoutVat="{ item }">
                {{ item.priceWithoutVat | formatRub }}
            </template>
            <template v-slot:item.SUMMAP="{ item }">
                <edit-field :disabled="!editable"
                            :rules="[rules.isNumber, rules.required, rules.positive]"
                            @save="save"
                            attribute="SUMMAP"
                            v-model="item"
                >
                    <template v-slot:cell>
                        {{ item.SUMMAP | formatRub }}
                    </template>
                </edit-field>
            </template>
            <template v-slot:item.sumWithoutVat="{ item }">
                {{ item.sumWithoutVat | formatRub }}
            </template>
            <template v-slot:item.STRANA="{ item }">
                <edit-field @save="save" attribute="STRANA" v-model="item"/>
            </template>
            <template v-slot:item.GTD="{ item }">
                <edit-field @save="save" attribute="GTD" v-model="item"/>
            </template>
            <template v-slot:item.PRIM="{ item }">
                <edit-field @save="save" attribute="PRIM" v-model="item"/>
            </template>
        </v-data-table>
        <order-import-lines :master-id="value.ID" v-else v-model="orderImportLines"/>
    </div>
</template>

<script>
    import tableMixin from "../../mixins/tableMixin";
    import utilsMixin from "../../mixins/utilsMixin";
    import OrderEdit from "./OrderEdit";
    import GoodName from "../good/GoodName";
    import OrderImportLines from "./OrderImportLines";
    import EditField from "../EditField";
    import {mapGetters} from "vuex";

    export default {
        name: "OrderLines",
        mixins: [tableMixin, utilsMixin],
        components: {OrderImportLines, OrderEdit, GoodName, EditField},
        props: {
            value: {
                type: Object,
                required: true,
            }
        },
        data() {
            return {
                options: {
                    with: ['category', 'good', 'name', 'order', 'seller'],
                    aggregateAttributes: [
                        'shopLinesQuantity', 'storeLinesQuantity',
                    ],
                    filterAttributes: [
                        'MASTER_ID',
                    ],
                    filterOperators: ['='],
                    filterValues: [this.value.ID],
                    itemsPerPage: -1,
                },
                mobileFiltersVisible: false,
                dependent: true,
                model: 'ORDER-LINE',
                orderImportLines: [],
            }
        },
        computed: {
            editable() {
                return this.$store.getters['AUTH/HAS_PERMISSION']('order-line.update') && this.value.STATUS === 0;
            },
            isOrderImportLinesEmpty() {
                return !this.orderImportLines.length;
            },
            items2() {
                return _.map(this.items, (v) => {
                    v.priceWithoutVat = v.SUMMAP / (100 + this.vat) * 100 / v.QUAN;
                    v.sumWithoutVat = v.SUMMAP / (100 + this.vat) * 100;
                    return v;
                })
            },
            ...mapGetters({vat: 'VAT'}),
        },
        methods: {
            inQuantityColor(item) {
                if (item.shopLinesQuantity + item.storeLinesQuantity === 0) return 'red--text';
                if (item.shopLinesQuantity + item.storeLinesQuantity < item.QUAN) return 'primary--text';
                return 'success--text';
            },
            importOpen(orderImportLines) {
                if (_.isArray(orderImportLines) && !_.isEmpty(orderImportLines)) {
                    this.orderImportLines = orderImportLines
                }
            },
            async remove(item) {
                try {
                    await this.$store.dispatch('ORDER-LINE/REMOVE', item.ID);
                    await this.reloadOrder();
                } catch (e) {}
            },
            async reloadOrder() {
                const payload = {
                    id: this.value.ID,
                    query: {
                        with: ['seller', 'employee'],
                        aggregateAttributes: [
                            'orderLinesCount', 'orderLinesSum', 'cashFlowsSum',
                        ],
                    }
                };
                await this.$store.dispatch('ORDER/GET', payload);
            },
            newOrderLine(id) {
                this.itemIds.push(id);
            }
        },
    }
</script>

<style scoped>

</style>
