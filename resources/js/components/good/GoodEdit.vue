<template>
    <v-container>
        <v-row>
            <category-select :disabled="true" :key="categoryKey" v-model="model.CATEGORYCODE"/>
            <name-select :error-messages="errors['item.NAMECODE']"
                         @save="nameSaved"
                         v-model="model.NAMECODE"
                         :key="nameKey"
                         :new-search="newName"
                         :disabled="notEditable"
            />
        </v-row>
        <v-row>
            <v-col cols="1">
                <v-text-field
                    :error-messages="errors['item.UNIT_I']"
                    label="Ед.изм."
                    v-model="model.UNIT_I"
                    :disabled="notEditable"
                />
            </v-col>
            <v-col cols="2">
                <v-text-field
                    :error-messages="errors['item.BODY']"
                    label="Корпус"
                    v-model="model.BODY"
                    :disabled="notEditable"
                />
            </v-col>
            <v-col cols="2">
                <v-text-field
                    :error-messages="errors['item.PRODUCER']"
                    label="Производитель"
                    v-model="model.PRODUCER"
                    :disabled="notEditable"
                />
            </v-col>
            <v-col cols="6">
                <v-text-field :error-messages="errors['item.PRIM']"
                              label="Описание"
                              v-model="model.PRIM"
                              :disabled="notEditable"
                />
            </v-col>
            <v-col cols="1">
                <v-btn :disabled="savePossible || notEditable" @click="save" fab :loading="loading">
                    <v-icon color="green">mdi-content-save</v-icon>
                </v-btn>
            </v-col>
        </v-row>
        <retail-price-edit v-model="retailPrice"/>
        <order-step-edit v-model="orderStep"/>
        <leftovers v-model="model"/>
        <good-info v-model="model.GOODSCODE" />
    </v-container>
</template>

<script>
    import CategorySelect from "../CategorySelect";
    import NameSelect from "../NameSelect";
    import RetailPriceEdit from "../Retail/RetailPriceEdit";
    import OrderStepEdit from "../order/OrderStepEdit";
    import editMixin from "../../mixins/editMixin";
    import Leftovers from "../Leftovers";
    import GoodInfo from "./GoodInfo";

    export default {
        name: "GoodEdit",
        components: {GoodInfo, Leftovers, RetailPriceEdit, NameSelect, CategorySelect, OrderStepEdit},
        mixins: [editMixin],
        props: {
            newName: {
                type: String,
                default: '',
            },
            goodPrototype: {
                type: Object,
                default: () => {},
            }
        },
        data() {
            return {
                NAMECODE: null,
                MODEL: 'GOOD',
                nameKey: 0,
                categoryKey: 100,
                dependent: true,
            }
        },
        computed: {
            notEditable() {
                return !this.$store.getters['AUTH/HAS_PERMISSION']('good.update');
            },
            retailPrice: {
                get() {
                    return (this.model && this.model.retailPrice) || {PRICECODE: 0, DOLLAR: 'F', GOODSCODE: this.model ? this.model.GOODSCODE : null}
                },
                set(val) {
                    this.model.retailPrice = val;
                }
            },
            orderStep: {
                get() {
                    return (this.model && this.model.orderStep) || {ID: 0, GOODSCODE: this.model ? this.model.GOODSCODE : null}
                },
                set(val) {
                    this.model.orderStep = val;
                }
            },
            options() {
                return this.$store.getters['AUTH/LOCAL_OPTION'](this.MODEL)
                    || {
                        with: [
                            'retailPrice', 'orderStep', 'retailStore', 'warehouse', 'name', 'category', 'goodNames'
                        ],
                        aggregateAttributes: [
                            'reservesQuantity',
                            'invoiceLinesQuantityTransit',
                            'reservesQuantityTransit',
                            'pickUpsTransitQuantity',
                            'retailOrderLinesNeedQuantity',
                            'orderLinesTransitQuantity',
                            'shopLinesTransitQuantity',
                            'storeLinesTransitQuantity',
                        ],
                    }
            },
        },
        watch: {
            model: {
                deep: true,
                handler: function (val) {
                    if (val && val.NAMECODE && this.NAMECODE !== val.NAMECODE) {
                        this.$store.dispatch('NAME/CACHE', {id: val.NAMECODE, query: {with: ['category']}})
                            .then((response) => {
                                this.model.CATEGORYCODE = response.CATEGORYCODE;
                                this.model.category = response.category
                                this.categoryKey += 1;
                            })
                    }
                    if (!val || !val.NAMECODE) {
                        this.CATEGORYCODE = null;
                    }
                    this.NAMECODE = val ? val.NAMECODE : null;
                }
            }
        },
        methods: {
            nameSaved(name) {
                this.model.CATEGORYCODE = name ? name.CATEGORYCODE : null;
                this.nameKey += 1;
                this.categoryKey += 1;
            }
        },
    }
</script>

<style scoped>

</style>
