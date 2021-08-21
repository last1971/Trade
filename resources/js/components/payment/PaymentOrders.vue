<template>
    <v-data-table
        hide-default-footer
        :headers="headers2"
        :items="items"
        :loading="loading"
        :multi-sort="true"
        :options.sync="options"
        :server-items-length="total"
        :loading-text="loadingText"
        show-select
        v-model="selected"
        item-key="id"
    >
        <template v-slot:top>
            <v-card>
                <v-card-actions>
                    <payment-order-add :value="value" @reload="reload" class="ml-2"/>
                    <v-btn :disabled="!canBeDeleted" rounded color="error" class="ml-2" @click="remove">
                        <v-icon left>mdi-delete</v-icon>
                        УДАЛИТЬ
                    </v-btn>
                </v-card-actions>
            </v-card>
        </template>
        <template v-slot:item.date="{ item }">
            {{ item.date | formatDate }}
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
                with: ['payment'],
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
            selected: [],
        }
    },
    computed: {
        canBeDeleted() {
            return this.selected.length;
        },
        headers2() {
            return this.headers.filter((h) => !h.additional);
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
        },
        async remove() {
            try {
                await Promise.all(this.selected.map((item) => {
                    return this.$store.dispatch('PAYMENT-ORDER/REMOVE', item.id);
                }));
                this.selected = [];
                this.updateItems();
                this.$emit('reload');
            } catch (e) {
                console.error(e);
            }
        }
    },
}
</script>

<style scoped>

</style>
