<template>
    <v-select :items="statuses" label="Статус" v-model="proxy" :multiple="multiple"/>
</template>

<script>
import utilsMixin from "../mixins/utilsMixin";

export default {
    name: "RetailOrderLineStatusSelect",
    mixins: [utilsMixin],
    props: {
        value: {
            type: Array,
            required: true,
            default: [0],
        },
        multiple: {
            type: Boolean,
            default: true,
        }
    },
    computed: {
        statuses() {
            const statuses = this.$store.getters['RETAIL-ORDER-STATUS/ALL'];
            const result = Object.keys(statuses).map((text) => {
                return {text, value: statuses[text]}
            });
            return result;
        },
    }
}
</script>

<style scoped>

</style>
