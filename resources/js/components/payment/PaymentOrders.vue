<template>
    <v-data-table
        hide-default-footer
        :headers="headers"
        :items="items"
        :loading="loading"
        :multi-sort="true"
        :options.sync="options"
        :server-items-length="total"
        :loading-text="loadingText"
        :single-expand="true"
        item-key="id"
    >
        <template v-slot:top>
            <v-container>
                <payment-order-add :value="value" @reload="reload"/>
            </v-container>
        </template>
    </v-data-table>
</template>

<script>
import tableMixin from "../../mixins/tableMixin";
import utilsMixin from "../../mixins/utilsMixin";
import PaymentOrderAdd from "./PaymentOrderAdd";

export default {
    name: "PaymentOrders",
    components: {PaymentOrderAdd},
    mixins: [tableMixin, utilsMixin],
    props: {
        value: {
            type: Object,
            required: true,
        }
    },
    data() {
        return {
            options: {
                filterAttributes: [
                    'payment_id',
                ],
                filterOperators: ['='],
                filterValues: [this.value.id],
                sortBy: ['date'],
                sortDesc: [false],
                itemsPerPage: -1,
            },
            mobileFiltersVisible: false,
            dependent: true,
            model: 'PAYMENT-ORDER',
        }
    },
    watch: {
        value() {
            this.options.filterValues = [this.value.id];
            this.updateItems();
        }
    },
    methods: {
        reload() {
            this.updateItems();
            this.$emit('reload');
        }
    },
}
</script>

<style scoped>

</style>
