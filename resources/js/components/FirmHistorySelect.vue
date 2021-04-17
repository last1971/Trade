<template>
    <model-select
        :disabled="disabled"
        :items-per-page="-1"
        :multiple="multiple"
        item-text="PRIM"
        item-value="ID"
        label="Реквизиты"
        model="firm-history"
        :filter-attributes="['FIRM_ID']"
        :filter-operators="['=']"
        :filter-values="[firmId]"
        v-model="proxy"
        :can-empty="canEmpty"
    ></model-select>
</template>

<script>
import ModelSelect from "./ModelSelect";
import utilsMixin from "../mixins/utilsMixin";

export default {
    name: "FirmHistorySelect",
    mixins: [utilsMixin],
    components: {ModelSelect},
    props: {
        value: {
            type: [Array, Number]
        },
        multiple: {
            type: Boolean,
            default: false
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        firmId: {
            type: Number,
        },
        canEmpty: {
            type: Boolean,
            default: false
        }
    },
}
</script>

<style scoped>

</style>
