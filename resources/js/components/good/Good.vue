<template>
    <model :aggregate-attributes="aggregateAttributes"
           :lines="GoodEdit"
           :with="this.with"
           date=""
           name="Товар"
           number="name.NAME"
           value="good"
    />
</template>

<script>
    import Model from '../Model'
    import GoodEdit from "./GoodEdit";

    export default {
        name: "Good",
        components: {Model},
        data() {
            return {
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
        computed: {
            GoodEdit() {
                return GoodEdit;
            }
        },
    }
</script>

<style scoped>

</style>
