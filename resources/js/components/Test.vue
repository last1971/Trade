<template>
    <delivery-time-select v-model="v"/>
</template>

<script>
    import DeliveryTimeSelect from "./DeliveryTimeSelect";
    export default {
        name: "Test",
        components: {DeliveryTimeSelect, },
        data() {
            return {
                v: null,
            }
        },
        computed: {

        },
        methods: {

        }
    }
</script>

<style scoped>

</style>
