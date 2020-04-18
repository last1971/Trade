<template>
    <model-select
        :disabled="disabled"
        :items-per-page="-1"
        :multiple="multiple"
        item-text="FIRMNAME"
        item-value="FIRM_ID"
        label="Фирма"
        model="firm"
        v-model="proxy"
    ></model-select>
</template>

<script>
    import ModelSelect from "./ModelSelect";
    import utilsMixin from "../mixins/utilsMixin";

    export default {
        name: "FirmSelect",
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
        },
    }
</script>

<style scoped>

</style>
