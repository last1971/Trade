<template>
    <div v-if="restricted">
        <v-text-field label="Сотрудник"
                      :disabled="true"
                      value="C E N S O R E D"
        ></v-text-field>
    </div>
    <model-select
        v-else
        :disabled="disabled"
        :items-per-page="-1"
        :multipe="multiple"
        item-text="FULLNAME"
        item-value="ID"
        label="Сотрудник"
        model="employee"
        v-model="proxy"
        :no-filter="false"
        :can-empty="canEmpty"
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
            canEmpty: {
                type: Boolean,
                default: false
            },
        },
        computed: {
        restricted() {
                return !(this.$store.getters['AUTH/HAS_PERMISSION']('employee.index')
                    && this.$store.getters['AUTH/HAS_PERMISSION']('employee.show'));
            }
        }
    }
</script>

<style scoped>

</style>
