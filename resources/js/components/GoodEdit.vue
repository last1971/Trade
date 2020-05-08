<template>
    <v-container>
        <v-row>
            <category-select :disabled="true" v-model="model.CATEGORYCODE"/>
            <name-select v-model="model.NAMECODE"/>
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
            }
        },
        computed: {
            retailPrice: {
                get() {
                    return this.model.retailPrice || {PRICECODE: 0, DOLLAR: 'F', GOODSCODE: this.good.GOODSCODE}
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
