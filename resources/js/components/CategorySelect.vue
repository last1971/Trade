<template>
    <model-select
        :dense="dense"
        :disabled="disabled"
        :multiple="multiple"
        item-text="CATEGORY"
        item-value="CATEGORYCODE"
        label="Категория"
        model="category"
        v-model="proxy"
    ></model-select>
</template>

<script>
    import utilsMixin from "../mixins/utilsMixin";
    import ModelSelect from "./ModelSelect";

    export default {
        name: "CategorySelect",
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
        },
    }
</script>

<style scoped>

</style>
