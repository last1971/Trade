<template>
    <v-container>
        <v-row>
            <category-select :disabled="true" v-model="model.CATEGORYCODE"/>
            <name-select v-model="model.NAMECODE"/>
        </v-row>
        <v-row>
            <v-col cols="1">
                <v-text-field label="Ед.изм." v-model="model.UNIT_I"/>
            </v-col>
            <v-col cols="2">
                <v-text-field label="Корпус" v-model="model.BODY"/>
            </v-col>
            <v-col cols="2">
                <v-text-field label="Производитель" v-model="model.PRODUCER"/>
            </v-col>
            <v-col cols="6">
                <v-text-field label="Описание"v-model="model.PRIM"/>
            </v-col>
            <v-col cols="1">
                <v-btn :disabled="savePossible" @click="save" fab :loading="loading">
                    <v-icon color="green">mdi-content-save</v-icon>
                </v-btn>
            </v-col>
        </v-row>
        <retail-price-edit v-model="retailPrice"/>
    </v-container>
</template>

<script>
    import CategorySelect from "./CategorySelect";
    import NameSelect from "./NameSelect";
    import RetailPriceEdit from "./RetailPriceEdit";
    import editMixin from "../mixins/editMixin";

    export default {
        name: "GoodEdit",
        components: {RetailPriceEdit, NameSelect, CategorySelect},
        mixins: [editMixin],
        data() {
            return {
                NAMECODE: null,
                MODEL: 'GOOD',
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
            }
        },
        watch: {
            model: {
                deep: true,
                handler: function (val) {
                    if (this.NAMECODE && val.NAMECODE && this.NAMECODE !== val.NAMECODE) {
                        this.$store.dispatch('NAME/CACHE', {id: val.NAMECODE, query: {with: ['category']}})
                            .then((response) => {
                                this.model.CATEGORYCODE = response.CATEGORYCODE;
                                this.model.category = response.category
                            })
                    }
                    this.NAMECODE = val.NAMECODE;
                }
            }
        },
    }
</script>

<style scoped>

</style>
