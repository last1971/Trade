<template>
    <invoice-lines-dependent :dependent-value="true" v-model="options">
        <template v-slot:top>
            <div class="title">Необходимо для счетов</div>
        </template>
    </invoice-lines-dependent>
</template>

<script>
    import InvoiceLinesDependent from "./InvoiceLinesDependent";

    export default {
        name: "FutureReserves",
        components: {InvoiceLinesDependent},
        props: {
            value: {
                type: Object,
                required: true,
            }
        },
        data() {
            return {
                options: {
                    with: ['category', 'name', 'good', 'invoice.buyer'],
                    aggregateAttributes: ['pickUpsQuantity', 'reservesQuantity'],
                    filterAttributes: [
                        'REALPRICE.GOODSCODE', 'notPickUp', 'invoice.STATUS'
                    ],
                    filterOperators: ['=', '=', 'IN'],
                    filterValues: [this.value.GOODSCODE, 1, [2, 3]],
                    sortBy: ['invoice.DATA'],
                    sortDesc: [false],
                    itemsPerPage: -1,
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
