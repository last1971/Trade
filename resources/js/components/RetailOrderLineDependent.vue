<template>
    <v-data-table
        :footer-props="{
            showFirstLastPage: true,
        }"
        :headers="mutatedHeaders"
        :hide-default-footer="hideDefaultFooter"
        :items="items"
        :loading="loading"
        :multi-sort="true"
        :options.sync="options"
        :server-items-length="total"
        item-key="ID"
        loading-text="Loading... Please wait"

    >
        <template v-slot:top>
            <slot name="top"/>
        </template>
        <template v-slot:item.retailOrder.DATA="{ item }">
            {{ item.retailOrder.DATA | formatDate }}
        </template>
        <template v-slot:item.PRICE="{ item }">
            {{ item.PRICE | formatRub }}
        </template>
    </v-data-table>
</template>

<script>
    import tableMixin from "../mixins/tableMixin";
    import utilsMixin from "../mixins/utilsMixin";

    export default {
        name: "RetailOrderLineDependent",
        mixins: [tableMixin, utilsMixin],
        props: {
            value: {
                type: Object,
                required: true,
            },
            hideDefaultFooter: {
                type: Boolean,
                default: false,
            },
            dependentValue: {
                type: Boolean,
                default: false,
            },
            removeHeaders: {
                type: Array,
                default: () => [],
            }
        },
        data() {
            return {
                mobileFiltersVisible: false,
                model: 'RETAIL-ORDER-LINE',
                dependent: this.dependentValue,
            }
        },
        computed: {
            options: {
                get() {
                    return this.value;
                },
                set(val) {
                    this.$emit('input', val);
                }
            },
            mutatedHeaders() {
                return this.headers.filter(
                    (header) => this.removeHeaders.find((rh) => rh === header.value) === undefined
                );

            }
        },
    }
</script>

<style scoped>

</style>
