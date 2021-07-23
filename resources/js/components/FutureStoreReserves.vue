<template>
    <invoice-lines-dependent :dependent-value="true"
                             :hide-default-footer="true"
                             :remove-headers="removeHeaders"
                             v-model="options"
    >
        <template v-slot:top v-if="title && topText" >
            <div class="title">{{ title }}</div>
        </template>
    </invoice-lines-dependent>
</template>

<script>
    import InvoiceLinesDependent from "./invoice/InvoiceLinesDependent";

    export default {
        name: "FutureReserves",
        components: {InvoiceLinesDependent},
        props: {
            value: {
                type: Object,
                required: true,
            },
            title: {
                type: [String, Boolean],
                default: 'Необходимо для счетов',
            },
            topText: {
                type: Boolean,
                default: true,
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
                    page: 1,
                },
                mobileFiltersVisible: false,
                dependent: true,
                model: 'RESERVE',
                removeHeaders: ['category.CATEGORY', 'name.NAME', 'good.BODY', 'good.PRODUCER'],
            }
        },
    }
</script>

<style scoped>

</style>
