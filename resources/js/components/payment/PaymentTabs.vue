<template>
    <v-card>
        <v-tabs
            v-model="tab"
            align-with-title
        >
            <v-tab>Счета на оплату</v-tab>
            <v-tab>Платежки</v-tab>
        </v-tabs>
        <v-tabs-items v-model="tab">
            <v-tab-item>
                <payments></payments>
            </v-tab-item>
            <v-tab-item>
                <payment-orders-tab></payment-orders-tab>
            </v-tab-item>
        </v-tabs-items>
    </v-card>
</template>

<script>
import Payments from "./Payments";
import PaymentOrdersTab from "./PaymentOrdersTab";

export default {
    beforeRouteEnter(to, from, next) {
        next(vm => vm.$store.commit('BREADCRUMBS/SET', [
            {text: 'Торговля', to: {name: 'home'}, exact: true},
            {text: 'Платежи', to: {name: 'payments'}, exact: true},
        ]));
    },
    name: "PaymentTabs",
    components: {PaymentOrdersTab, Payments},
    data() {
        return {
            tab: null,
        }
    }
}
</script>

<style scoped>

</style>
