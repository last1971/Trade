<template>
    <model-select
        :disabled="disabled"
        :items-per-page="-1"
        :multiple="multiple"
        item-text="name"
        item-value="id"
        label="Роли"
        model="role"
        v-model="proxy"
    ></model-select>
</template>

<script>
    import ModelSelect from "./ModelSelect";

    export default {
        name: "RoleSelect",
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
                    if (_.isArray(this.value)) {
                        return this.value.map((v) => _.isObject(v) ? v.id : v)
                    }
                    return this.value
                },
                set(val) {
                    if (_.isArray(val)) {
                        this.$emit('input', this.$store.getters['ROLE/ALL'].filter((v) => val.indexOf(v.id) >= 0));
                    } else {
                        this.$emit('input', val)
                    }
                }
            }
        },
    }
</script>

<style scoped>

</style>


