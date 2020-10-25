<template>
    <v-btn :to="{name: 'goods-list'}" color="primary" rounded>
        <div v-if="opened">
            <span v-if="buyer">
                Для {{ buyer }}
            </span>
            <span v-else>
                Спиок из
            </span>
            {{ totalCount }} товаров на сумму {{ totalAmount | formatRub }}
        </div>
        <div v-else>
            Список пуст
        </div>
    </v-btn>
</template>

<script>
export default {
    name: "GoodsListButton",
    computed: {
        opened() {
            return this.$store.getters['GOODS-LIST/OPENED'];
        },
        totalCount() {
            return this.$store.getters['GOODS-LIST/TOTAL-COUNT'];
        },
        totalAmount() {
            return this.$store.getters['GOODS-LIST/TOTAL-AMOUNT'];
        },
        buyer() {
            const buyer = this.$store.getters['GOODS-LIST/BUYER'];
            return buyer ? buyer.SHORTNAME : null;
        }
    }
}
</script>

<style scoped>

</style>
