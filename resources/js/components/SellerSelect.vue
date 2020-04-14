<template>
    <model-select
        :disabled="disabled"
        :multipe="multiple"
        item-text="NAMEPOST"
        item-value="WHEREISPOSTCODE"
        label="Поставщик"
        model="seller"
        v-model="proxy"
    ></model-select>
</template>

<script>
    import utilsMixin from "../mixins/utilsMixin";
    import ModelSelect from "./ModelSelect";

    export default {
        name: "SellerSelect",
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
