<template>
    <model-select
        :disabled="disabled"
        :multipe="multiple"
        item-text="SHORTNAME"
        item-value="POKUPATCODE"
        label="Покупатель"
        model="buyer"
        v-model="proxy"
    ></model-select>
</template>

<script>
    import ModelSelect from "./ModelSelect";

    export default {
        name: "BuyerSelect",
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
        computed: {
            proxy: {
                get() {
                    return this.value
                },
                set(val) {
                    this.$emit('input', val)
                }
            }
        },
    }
</script>

<style scoped>

</style>
