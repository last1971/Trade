<template>
    <div>
        <good-select :new-search="newSearch" ref="test" v-model="test"/>
        <v-btn @click="click">test</v-btn>
    </div>
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
                newSearch: '123',
            }
        },
        methods: {
            click() {
                const el = this.$refs.test.$el;
                let childData = el.querySelectorAll("input")[0];
                childData.focus()
                this.newSearch = 'max232cpe'
            }
        }
    }
</script>

<style scoped>

</style>
