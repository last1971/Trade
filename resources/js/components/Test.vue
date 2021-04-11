<template>
    <div>
        <v-text-field v-model="newSearch"/>
        <good-select :new-search="newSearch" v-model="value"/>
    </div>
</template>

<script>


    import GoodSelect from "./good/GoodSelect";
    export default {
        name: "Test",
        components: {GoodSelect},
        data() {
            return {
                newSearch: '2sd1710',
                value: null,
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
