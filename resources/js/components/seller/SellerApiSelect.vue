<template>
    <v-autocomplete
        v-model="proxy"
        :auto-select-first="true"
        item-value="sellerId"
        item-text="name"
        :items="sellers"
    />
</template>

<script>
import utilsMixin from "../../mixins/utilsMixin";

export default {
    name: "SellerApiSelect",
    mixins: [utilsMixin],
    props: {
        value: {
            validate: (v) => typeof v === 'number' || v === null,
        },
    },
    computed: {
        sellers() {
            const sellers = this.$store.getters['SELLER-PRICE/SELLERS'];
            if (_.isEmpty(sellers)) this.$store.dispatch('SELLER-PRICE/GET_SELLERS');
            return sellers;
        }
    }
}
</script>

<style scoped>

</style>
