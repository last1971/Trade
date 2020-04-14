<template>
    <v-data-table
        :footer-props="{
            showFirstLastPage: true,
        }"
        :headers="headers"
        :items="items"
        :loading="loading"
        :multi-sort="true"
        :options.sync="options"
        :server-items-length="total"
        item-key="ID"
        loading-text="Loading... Please wait"
    >
        <template v-slot:top>

        </template>
        <template v-slot:item.DATA_PRIH="{ item }">
            {{ (item.DATA_PRIH || value.DATA_PRIH) | formatDate }}
        </template>
        <template v-slot:item.inQuantity="{ item }">
            <div :class="inQuantityColor(item)">
                {{ item.shopLinesQuantity + item.storeLinesQuantity }}
            </div>
        </template>
        <template v-slot:item.PRICE="{ item }">
            {{ item.PRICE | formatRub }}
        </template>
        <template v-slot:item.SUMMAP="{ item }">
            {{ item.SUMMAP | formatRub }}
        </template>
    </v-data-table>
</template>

<script>
    import tableMixin from "../mixins/tableMixin";
    import utilsMixin from "../mixins/utilsMixin";

    export default {
        name: "OrderLines",
        mixins: [tableMixin, utilsMixin],
        components: {},
        props: {
            value: {
                type: Object,
                required: true,
            }
        },
        data() {
            return {
                options: {
                    with: ['category', 'good', 'name'],
                    aggregateAttributes: [
                        'shopLinesQuantity', 'storeLinesQuantity',
                    ],
                    filterAttributes: [
                        'MASTER_ID',
                    ],
                    filterOperators: ['='],
                    filterValues: [this.value.ID],
                },
                mobileFiltersVisible: false,
                dependent: true,
            }
        },
        methods: {
            inQuantityColor(item) {
                if (item.shopLinesQuantity + item.storeLinesQuantity === 0) return 'red--text';
                if (item.shopLinesQuantity + item.storeLinesQuantity < item.QUAN) return 'primary--text';
                return 'success--text';
            }
        }
    }
</script>

<style scoped>

</style>
