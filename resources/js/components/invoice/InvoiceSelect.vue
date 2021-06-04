<template>

    <model-select
        :dense="dense"
        :disabled="disabled"
        item-text="NS"
        item-value="SCODE"
        :label="label"
        model="invoice"
        v-model="proxy"
        :with="with_"
        :aggregate-attributes="aggregateAttributes"
        :filter-attributes="filterAttributes"
        :filter-operators="filterOperators"
        :filter-values="filterValues"
        :sort-by="sortBy"
        :sort-desc="sortDesc"
    >
        <template v-slot:item="{ item, maxLength }">
            <invoice-in-line :invoice="item"/>
        </template>
        <template v-slot:selection="{ item }">
            <invoice-in-line :invoice="item"/>
        </template>
    </model-select>

</template>

<script>
import utilsMixin from "../../mixins/utilsMixin";
import ModelSelect from "../ModelSelect";
import InvoiceInLine from "./InvoiceInLine";

export default {
    name: "InvoiceSelect",
    components: {InvoiceInLine, ModelSelect},
    mixins: [utilsMixin],
    props: {
        value: {
            type: [Array, Number, String]
        },
        dense: {
            type: Boolean,
            default: false,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        label: {
            type: String,
            default: 'Счет',
        }
    },
    data() {
        return {
            with_: ['buyer', 'employee', 'firm'],
            aggregateAttributes: [
                'invoiceLinesCount', 'invoiceLinesSum', 'cashFlowsSum', 'transferOutLinesSum'
            ],
            filterAttributes: [
                'STATUS',
            ],
            filterOperators: [
                '=',
            ],
            filterValues: [0],
            sortBy: ['DATA'],
            sortDesc: [true],
        }
    }
}
</script>

<style scoped>

</style>
