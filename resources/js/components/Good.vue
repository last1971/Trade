<template>
    <div v-if="good">
        <v-container>
            <v-row>
                <category-select :disabled="true" v-model="good.CATEGORYCODE"/>
                <name-select v-model="good.NAMECODE"/>
            </v-row>
        </v-container>
    </div>
</template>

<script>
    import CategorySelect from "./CategorySelect";
    import NameSelect from "./NameSelect";

    export default {
        name: "Good",
        components: {NameSelect, CategorySelect},
        data() {
            return {
                good: null,
                options: {
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
                },
                NAMECODE: null,
            }
        },
        watch: {
            good: {
                deep: true,
                handler: function (val) {
                    if (this.NAMECODE && val.NAMECODE && this.NAMECODE !== val.NAMECODE) {
                        this.$store.dispatch('NAME/CACHE', {id: val.NAMECODE, query: {with: ['category']}})
                            .then((response) => {
                                this.good.CATEGORYCODE = response.CATEGORYCODE;
                                this.good.category = response.category
                            })
                    }
                    this.NAMECODE = val.NAMECODE;
                }
            }
        },
        methods: {
            getGood() {
                const {id} = this.$route.params
                this.$store.dispatch('GOOD/GET', {id, query: this.options})
                    .then((response) => this.good = response);

            }
        },
        beforeRouteEnter(to, from, next) {
            next(vm => {
                vm.getGood();
            });
        },
        beforeRouteUpdate(to, from, next) {
            this.getGood();
            next();
        }
    }
</script>

<style scoped>

</style>
