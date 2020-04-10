<template>
    <v-select :items="statuses" label="Статус" v-model="proxy">

    </v-select>
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
            }
        },
        computed: {
            statuses() {
                return this.$store.getters['INVOICESTATUS/ALL'].map((text, value) => {
                    return {text, value}
                })
            },
        }
    }
</script>

<style scoped>

</style>
