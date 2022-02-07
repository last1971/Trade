<template>
    <div>
        <seller-api-file-select-new />
    </div>
</template>

<script>
    import SellerApiFileSelectNew from "./seller/SellerApi/SellerApiFileSelectNew";
    export default {
        name: "Test",
        components: {SellerApiFileSelectNew, },
        data() {
            return {
                goodId: 395925,
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
