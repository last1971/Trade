<template>
    <div>
        <good-info v-model="goodId"/>
    </div>
</template>

<script>
    import GoodInfo from "./good/GoodInfo";
    export default {
        name: "Test",
        components: {GoodInfo,  },
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
