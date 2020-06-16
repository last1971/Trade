<template>
    <v-data-table
        :headers="headers"
        :items="value"
        item-key="id"
    >
        <template v-slot:item.GOODSCODE="{ item }">
            <good-select v-model="item.GOODSCODE"/>
        </template>
        <template v-slot:item.price="{ item }">{{ item.price | formatRub }}</template>
        <template v-slot:item.amount="{ item }">{{ item.amount | formatRub }}</template>
    </v-data-table>
</template>

<script>
    import GoodSelect from "./GoodSelect";

    export default {
        name: "OrderImportLines",
        components: {GoodSelect},
        props: {
            value: {
                type: Array,
            }
        },
        computed: {
            headers() {
                return this.$store.getters['ORDER-IMPORT-LINE/HEADERS'];
            }
        }
    }
</script>

<style scoped>

</style>
