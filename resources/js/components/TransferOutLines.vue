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
            <transfer-out-edit v-model="transferOut"/>
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
            transferOut: {
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
                    filterValues: [this.transferOut.SFCODE],
                },
                mobileFiltersVisible: false,
                dependent: true,
            }
        },
    }
</script>

<style scoped>

</style>
