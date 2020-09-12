<template>
    <v-container>
        <v-row>
            <category-select :disabled="true" :key="categoryKey" v-model="model.CATEGORYCODE"/>
            <name-select :error-messages="errors['item.NAMECODE']"
                         @save="nameSaved"
                         v-model="model.NAMECODE"
                         :key="nameKey"
            />
        </v-row>
        <v-row>
            <v-col cols="1">
                <v-text-field :error-messages="errors['item.UNIT_I']" label="Ед.изм." v-model="model.UNIT_I"/>
            </v-col>
            <v-col cols="2">
                <v-text-field :error-messages="errors['item.BODY']" label="Корпус" v-model="model.BODY"/>
            </v-col>
            <v-col cols="2">
                <v-text-field :error-messages="errors['item.PRODUCER']" label="Производитель" v-model="model.PRODUCER"/>
            </v-col>
            <v-col cols="6">
                <v-text-field :error-messages="errors['item.PRIM']" label="Описание" v-model="model.PRIM"/>
            </v-col>
            <v-col cols="1">
                <v-btn :disabled="savePossible" @click="save" fab :loading="loading">
                    <v-icon color="green">mdi-content-save</v-icon>
                </v-btn>
            </v-col>
        </v-row>
        <retail-price-edit v-model="retailPrice"/>
        <order-step-edit v-model="orderStep"/>
        <leftovers v-model="model"/>
    </v-container>
</template>

<script>
    import CategorySelect from "./CategorySelect";
    import NameSelect from "./NameSelect";
    import RetailPriceEdit from "./RetailPriceEdit";
    import OrderStepEdit from "./order/OrderStepEdit";
    import editMixin from "../mixins/editMixin";
    import Leftovers from "./Leftovers";

    export default {
        name: "GoodEdit",
        components: {Leftovers, RetailPriceEdit, NameSelect, CategorySelect, OrderStepEdit},
        mixins: [editMixin],
        data() {
            return {
                NAMECODE: null,
                MODEL: 'GOOD',
                nameKey: 0,
                categoryKey: 100,
            }
        },
        computed: {
            retailPrice: {
                get() {
                    return this.model.retailPrice || {PRICECODE: 0, DOLLAR: 'F', GOODSCODE: this.model.GOODSCODE}
                },
                set(val) {
                    this.model.retailPrice = val;
                }
            },
            orderStep: {
                get() {
                    return this.model.orderStep || {ID: 0, GOODSCODE: this.model.GOODSCODE}
                },
                set(val) {
                    this.model.orderStep = val;
                }
            }
        },
        watch: {
            model: {
                deep: true,
                handler: function (val) {
                    if (val.NAMECODE && this.NAMECODE !== val.NAMECODE) {
                        this.$store.dispatch('NAME/CACHE', {id: val.NAMECODE, query: {with: ['category']}})
                            .then((response) => {
                                this.model.CATEGORYCODE = response.CATEGORYCODE;
                                this.model.category = response.category
                                this.categoryKey += 1;
                            })
                    }
                    if (!val.NAMECODE) {
                        this.CATEGORYCODE = null;
                    }
                    this.NAMECODE = val.NAMECODE;
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
