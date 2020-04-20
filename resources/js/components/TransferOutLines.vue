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
        item-key="REALPRICEFCODE"
        loading-text="Loading... Please wait"
    >
        <template v-slot:top>
            <transfer-out-edit v-model="value"/>
        </template>
        <template v-slot:item.name.NAME="{ item }">
            <v-tooltip top>
                <template v-slot:activator="{ on }">
                    <span v-on="on">{{ item.name.NAME }}</span>
                </template>
                <span>{{ item.good.PRIM.trim() || 'описания нет' }}</span>
            </v-tooltip>
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
    import TransferOutEdit from "./TransferOutEdit";

    export default {
        name: "TransferOutLines",
        components: {TransferOutEdit},
        props: {
            value: {
                type: Object,
                required: true,
            }
        },
        mixins: [tableMixin, utilsMixin],
        data() {
            return {
                options: {
                    with: ['category', 'good', 'name'],
                    filterAttributes: [
                        'SFCODE',
                    ],
                    filterOperators: ['='],
                    filterValues: [this.value.SFCODE],
                },
                mobileFiltersVisible: false,
                dependent: true,
                model: 'TRANSFER-OUT-LINE',
            }
        },
    }
</script>

<style scoped>

</style>
