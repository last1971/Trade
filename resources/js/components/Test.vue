<template>
    <div>
    <seller-api-select v-model="sellerId"/>
    <seller-order-select v-model="v" :seller-id="sellerId"/>
    </div>
</template>

<script>
    import SellerApiSelect from "./seller/SellerApiSelect";
    import SellerOrderSelect from "./seller/SellerOrderSelect";
    export default {
        name: "Test",
        components: {SellerOrderSelect, SellerApiSelect,  },
        data() {
            return {
                v: null,
                sellerId: null,
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
