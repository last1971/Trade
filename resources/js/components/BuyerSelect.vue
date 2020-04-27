<template>
    <model-select
        :disabled="disabled"
        :multiple="multiple"
        item-text="SHORTNAME"
        item-value="POKUPATCODE"
        label="Покупатель"
        model="buyer"
        v-model="proxy"
        :dense="dense"
        :error-messages="errorMessages"
    ></model-select>
</template>

<script>
    import ModelSelect from "./ModelSelect";
    import utilsMixin from "../mixins/utilsMixin";

    export default {
        name: "BuyerSelect",
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
            dense: {
                type: Boolean,
                default: false,
            },
            errorMessages: {
                type: Array,
                default: () => [],
            }
        },
    }
</script>

<style scoped>

</style>
