<template>
    <model-select
        :disabled="disabled"
        :items-per-page="-1"
        :multipe="multiple"
        item-text="FULLNAME"
        item-value="ID"
        label="Сотрудник"
        model="employee"
        v-model="proxy"
        :no-filter="false"
    ></model-select>
</template>

<script>
    import utilsMixin from "../mixins/utilsMixin";
    import ModelSelect from "./ModelSelect";

    export default {
        name: "EmployeeSelect",
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
