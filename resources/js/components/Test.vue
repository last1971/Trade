<template>
    <good-select v-model="test"/>
</template>

<script>
    import GoodSelect from "./GoodSelect";
    import NameSelect from "./NameSelect";

    export default {
        name: "Test",
        components: {NameSelect, GoodSelect},
        data() {
            return {
                test: null,
            }
        }
    }
</script>

<style scoped>

</style>
