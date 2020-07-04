<template>
    <v-select :disabled="disabled" :items="statuses" @change="change" label="Статус" v-model="proxy"/>
</template>

<script>
    import utilsMixin from "../mixins/utilsMixin";

    export default {
        name: "InvoiceStatusSelect",
        mixins: [utilsMixin],
        props: {
            value: {
                type: Number,
                required: true,
                default: 0
            },
            disabled: {
                type: Boolean,
                default: false,
            }
        },
        computed: {
            statuses() {
                return this.$store.getters['INVOICESTATUS/ALL'].map((text, value) => {
                    return {text, value}
                })
            },
        },
        methods: {
            change(payload) {
                this.$emit('change', payload)
            }
        }
    }
</script>

<style scoped>

</style>
