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
            <transfer-out-edit v-model="value" @input="proxyInput"/>
        </template>
        <template v-slot:item.name.NAME="{ item }">
            <good-name v-model="item" :prim="item.good.PRIM"/>
        </template>
        <template v-slot:item.PRICE="{ item }">
            {{ item.PRICE | formatRub }}
        </template>
        <template v-slot:item.SUMMAP="{ item }">
            {{ item.SUMMAP | formatRub }}
        </template>
        <template v-slot:item.STRANA="{ item }">
            <edit-field @save="save" attribute="STRANA" v-model="item"/>
        </template>
        <template v-slot:item.GTD="{ item }">
            <edit-field @save="save" attribute="GTD" v-model="item"/>
        </template>
    </v-data-table>
</template>

<script>
    import tableMixin from "../mixins/tableMixin";
    import utilsMixin from "../mixins/utilsMixin";
    import TransferOutEdit from "./TransferOutEdit";
    import GoodName from "./GoodName";
    import EditField from "./EditField";
    import tableOptionsRouteMixin from "../mixins/tableOptionsRouteMixin";

    export default {
        name: "TransferOutLines",
        components: {TransferOutEdit, GoodName, EditField},
        props: {
            value: {
                type: Object,
                required: true,
            }
        },
        mixins: [tableMixin, tableOptionsRouteMixin, utilsMixin],
        data() {
            return {
                options: {
                    with: ['category', 'good', 'name'],
                    filterAttributes: [
                        'SFCODE',
                    ],
                    filterOperators: ['='],
                    filterValues: [this.value.SFCODE],
                    itemsPerPage: -1,
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
