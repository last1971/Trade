<template>
    <v-container fluid>
        <v-card>
            <v-card-title>Долги и отгрузки</v-card-title>
            <v-tabs v-model="tab" align-with-title>
                <v-tab>Долги</v-tab>
                <v-tab>Отчёт по отгрузкам</v-tab>
            </v-tabs>
            <v-tabs-items v-model="tab">
                <v-tab-item :key="0">
                    <buyer-debt :key="debtKey"/>
                </v-tab-item>
                <v-tab-item :key="1">
                    <buyer-shipments-report @open-debt="openDebt"/>
                </v-tab-item>
            </v-tabs-items>
        </v-card>
    </v-container>
</template>

<script>
    import BuyerDebt from "./BuyerDebt";
    import BuyerShipmentsReport from "./buyerDebt/BuyerShipmentsReport";
    import createLocalStorageSync from "../helpers/localStorage";

    const filterStorage = createLocalStorageSync('buyer_debt_filter');

    export default {
        name: "BuyerDebtPage",
        components: {BuyerDebt, BuyerShipmentsReport},
        data: () => ({
            tab: null,
            debtKey: 0,
        }),
        methods: {
            // Из сводной таблицы нажали «Долги» — подставляем покупателя и дату, переключаемся на вкладку «Долги».
            // BuyerDebt читает фильтр из localStorage в created(), поэтому бампаем :key, чтобы он перемонтировался.
            openDebt({buyer, from}) {
                filterStorage.set({buyer, from: from || null});
                this.debtKey++;
                this.tab = 0;
            },
        },
    }
</script>

<style scoped>

</style>
