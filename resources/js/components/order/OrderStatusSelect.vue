<template>
    <v-select :items="statuses" label="Статус" v-model="proxy"/>
</template>

<script>
    import utilsMixin from "../../mixins/utilsMixin";

    export default {
        name: "OrderStatusSelect",
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
                return this.$store.getters['ORDERSTATUS/ALL'].map((text, value) => {
                    return {text, value}
                })
            },
        }
    }
</script>

<style scoped>

</style>
