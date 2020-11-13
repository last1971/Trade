<template>
    <retail-order-line-dependent :hide-default-footer="true" v-model="options" :remove-headers="['good.name.NAME']">
        <template v-slot:top>
            <div class="title">Розничные заказы</div>
        </template>
    </retail-order-line-dependent>
</template>

<script>
    import RetailOrderLineDependent from "./RetailOrderLineDependent";

    export default {
        name: "RetailOrderLinesInWork",
        components: {RetailOrderLineDependent},
        props: {
            value: {
                type: Object,
                required: true,
            }
        },
        data() {
            return {
                options: {
                    with: ['retailOrder.buyer'],
                    filterAttributes: ['retailOrderLinesRemainingQuantity', 'GOODSCODE', 'ROZN_DETAIL.STATUS'],
                    filterOperators: ['=', '=', 'IN'],
                    filterValues: [1, this.value.GOODSCODE, [1, 2, 3, 5, 6]],
                    sortBy: ['retailOrder.DATA'],
                    sortDesc: [false],
                    itemsPerPage: -1,
                    page: 1,
                }
            }
        }
    }
</script>

<style scoped>

</style>
