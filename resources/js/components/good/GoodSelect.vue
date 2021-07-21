<template>
    <model-select
        :aggregate-attributes="aggregateAttributes"
        :filter-attributes="filterAttributes"
        :filter-operators="filterOperators"
        :filter-values="filterValues"
        :dense="dense"
        :disabled="disabled"
        :get-value="getValue"
        :key="reload"
        :with="with_"
        item-text="name.NAME"
        item-value="GOODSCODE"
        :label="label"
        model="good"
        v-model="proxy"
        :new-search="testName"
        :smart-name="proxySmartName"
        @clearSearchName="$emit('clearSearchName')"
        @focus="testName = newSearch"
    >
        <template v-slot:item="{ item, maxLength }">
            <good-in-string v-model="item" :rows="rows"/>
        </template>
        <template v-slot:selection="{ item }">
            <good-in-string v-model="item" />
        </template>
        <template v-slot:prepend>
            <v-dialog v-model="goodEdit">
                <template v-slot:activator="{ on }">
                    <v-btn icon v-on="on">
                        <v-icon color="green">mdi-content-save-edit</v-icon>
                    </v-btn>
                </template>
                <v-card>
                    <v-card-title class="headline">
                        <span class="headline">Товар</span>
                        <v-spacer/>
                        <v-btn @click="goodEdit = false" icon right>
                            <v-icon color="red">
                                mdi-close
                            </v-icon>
                        </v-btn>
                    </v-card-title>
                    <good-edit @input="goodEdit = false" v-model="good" :new-name="newSearch"/>
                </v-card>
            </v-dialog>
        </template>
        <template v-slot:append>
            <v-btn icon left @click.stop="proxySmartName = !proxySmartName">
                <v-icon v-if="proxySmartName">mdi-clipboard-check-outline</v-icon>
                <v-icon v-else>mdi-clipboard-outline</v-icon>
            </v-btn>
        </template>
    </model-select>
</template>

<script>
    import utilsMixin from "../../mixins/utilsMixin";
    import ModelSelect from "../ModelSelect";
    import GoodInString from "./GoodInString";
    import GoodEdit from "./GoodEdit";

    export default {
        name: "GoodSelect",
        mixins: [utilsMixin],
        components: {GoodEdit, GoodInString, ModelSelect},
        props: {
            value: {type: [Array, Number, String]},
            disabled: {type: Boolean, default: false},
            dense: {type: Boolean, default: false},
            newSearch: {type: String, default: ''},
            smartName: {type: Boolean, default: false},
            goodPrototype: {type: Object, default: () => {}}
        },
        data() {
            return {
                with_: ['name', 'retailStore', 'warehouse', 'orderStep', 'category', 'retailPrice'],
                aggregateAttributes: [
                    'reservesQuantity',
                    'invoiceLinesQuantity',
                    'invoiceLinesQuantityTransit',
                    'reservesQuantityTransit',
                    'pickUpsTransitQuantity',
                    'retailOrderLinesNeedQuantity',
                    'orderLinesTransitQuantity',
                    'shopLinesTransitQuantity',
                    'storeLinesQuantity',
                    'storeLinesTransitQuantity',
                    'transferOutLinesQuantity',
                ],
                filterAttributes: ['HIDDEN'],
                filterOperators: ['='],
                filterValues: [0],
                goodEdit: false,
                getValue: false,
                reload: 0,
                label: this.dense ? null : 'Товар',
                testName: null,
                proxySmartName: this.smartName
            }
        },
        computed: {
            proxy: {
                get() {
                    return this.value
                },
                set(val) {
                    this.$emit('input', val)
                }
            },
            MODEL() {
                return _.toUpper(this.model);
            },
            good: {
                get() {
                    return this.proxy
                        ? this.$store.getters['GOOD/GET'](this.proxy)
                        : _.isEmpty(this.goodPrototype)
                            ? this.$store.getters['GOOD/GET'](0)
                            : {
                                GOODSCODE: 0,
                                NAMECODE: null,
                                YEARP: '-',
                                UNIT_I: 'шт.',
                                BODY: this.goodPrototype.case,
                                PRIM: this.goodPrototype.remark
                                    ? this.goodPrototype.remark.substr(0, 60)
                                    : '',
                                PRODUCER: this.goodPrototype.producer,
                            }
                },
                set(val) {
                    this.$emit('input', val.GOODSCODE);
                    this.getValue = !this.getValue;
                    this.reload += 1;
                }
            },
            rows() {
                return this.$vuetify.breakpoint.name === 'xl' ? 1 : 2;
            }
        },
        watch: {
            smartName(v) {
                this.proxySmartName = v;
            },
            proxySmartName(v) {
                this.$emit('update:smartName', v);
            },
        },
        methods: {
            padEnd(v, l) {
                return _.padEnd(v, l, '.');
            },
        }
    }
</script>

<style scoped>

</style>
