<template>
    <v-container fluid>
        <v-card>
            <v-card-title>Рекомендация к закупке</v-card-title>
            <v-tabs v-model="tab" align-with-title>
                <v-tab>Лист «что закупить»</v-tab>
                <v-tab>Индивидуально</v-tab>
            </v-tabs>
            <v-tabs-items v-model="tab">
                <v-tab-item :key="0">
                    <replenish-list @calc="onCalc"/>
                </v-tab-item>
                <v-tab-item :key="1">
                    <replenish-good :inject="injected"/>
                </v-tab-item>
            </v-tabs-items>
        </v-card>
    </v-container>
</template>

<script>
    import ReplenishList from "./replenish/ReplenishList";
    import ReplenishGood from "./replenish/ReplenishGood";

    export default {
        name: "Replenish",
        components: {ReplenishList, ReplenishGood},
        beforeRouteEnter(to, from, next) {
            next(vm => vm.$store.commit('BREADCRUMBS/SET', [
                {text: 'Торговля', to: {name: 'home'}, exact: true},
                {text: 'Закупка', to: {name: 'replenish'}, exact: true},
            ]));
        },
        data: () => ({
            tab: null,
            injected: null,
        }),
        methods: {
            // Из «Листа» нажали «Посчитать индивидуально» — переключаемся на вкладку 2 и передаём позицию.
            onCalc(payload) {
                // Новый объект каждый раз, чтобы watch во вкладке сработал и на тот же код.
                this.injected = {...payload};
                this.tab = 1;
            },
        },
    }
</script>

<style scoped>

</style>
