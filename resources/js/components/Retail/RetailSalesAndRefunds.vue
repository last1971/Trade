<template>
    <div>
    <v-tabs v-model="tab" align-with-title>
        <v-tab>Продажи</v-tab>
        <v-tab>Возвраты</v-tab>
    </v-tabs>
    <v-tabs-items v-model="tab">
        <v-tab-item :key="0">
            <retail-sales/>
        </v-tab-item>
        <v-tab-item :key="1">
            <reatil-store-returns/>
        </v-tab-item>
    </v-tabs-items>
    </div>
</template>

<script>
import RetailSales from "./RetailSales";
import ReatilStoreReturns from "./ReatilStoreReturns";
export default {
    beforeRouteEnter(to, from, next) {
        next(vm => vm.$store.commit('BREADCRUMBS/SET', [
            {text: 'Торговля', to: {name: 'home'}, exact: true},
            {text: 'Розн.продажи', to: {name: 'retail-sales'}, exact: true},
        ]));
    },
    name: "RetailSalesAndRefunds",
    components: {ReatilStoreReturns, RetailSales},
    data() {
        return {
            tab: null,
        }
    }
}
</script>

<style scoped>

</style>
