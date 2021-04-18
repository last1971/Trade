<template>
    <v-data-table
        :headers="headers"
        :items="items"
        :loading="loading"
        :multi-sort="true"
        :options.sync="options"
        :server-items-length="total"
        item-key="RESERVCODE"
        loading-text="Loading... Please wait"
        :hide-default-footer="true"
    >
        <template v-slot:top v-if="topText">
            <div class="title">{{ topText }}</div>
        </template>
        <template v-slot:item.DATA="{ item }">
            {{ item.DATA | formatDate }}
        </template>
        <template v-slot:item.SCODE="{ item }">
            <router-link :to="{ name: 'invoice', params: { id: item.SCODE }}" v-if="item.SCODE">
                Счет № {{ item.invoice.NS }} от {{ item.invoice.DATA | formatDate }}
            </router-link>
            <div v-else>
                Розничный заказ от {{ item.retailOrderLine.retailOrder.DATA | formatDate }}
            </div>
        </template>
        <template v-slot:item.PRIM="{ item }">
            <div v-if="item.SCODE">
                {{ item.invoice.buyer.SHORTNAME }}
            </div>
            <div v-else>
                {{ item.retailOrderLine.retailOrder.buyer.SHORTNAME }}
            </div>
        </template>
    </v-data-table>
</template>

<script>
    import tableMixin from "../mixins/tableMixin";
    import utilsMixin from "../mixins/utilsMixin";

    export default {
        name: "Reserves",
        mixins: [tableMixin, utilsMixin],
        props: {
            value: {
                type: Object,
                required: true,
            },
            topText: {
                type: [String, Boolean],
                default: 'Резервы',
            }
        },
        data() {
            return {
                options: {
                    with: ['invoice.buyer', 'retailOrderLine.retailOrder.buyer'],
                    filterAttributes: [
                        'GOODSCODE', 'QUANSHOP+QUANSKLAD'
                    ],
                    filterOperators: ['=', '>'],
                    filterValues: [this.value.GOODSCODE, 0],
                    sortBy: ['DATA'],
                    sortDesc: [false],
                    itemsPerPage: -1,
                    page: 1,
                },
                mobileFiltersVisible: false,
                dependent: true,
                model: 'RESERVE',
            }
        },
    }
</script>

<style scoped>

</style>
