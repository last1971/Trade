<template>
    <model :aggregate-attributes="aggregateAttributes"
           :crumb-icon="crumbIcon"
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
    import {chzIcon} from "../../store/marking";

    export default {
        name: "Good",
        components: {Model},
        created() {
            // Для значка ЧЗ в крошке (no-op, если коды уже загружены).
            this.$store.dispatch('MARKING/FETCH_GOODS');
        },
        data() {
            return {
                // Значок ЧЗ в крошке у маркируемого товара.
                crumbIcon: chzIcon,
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
