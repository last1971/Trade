<template>
    <div>
        <v-card-text>
            <div class="d-flex flex-wrap align-center" style="gap: 16px">
                <v-text-field
                    v-model.number="lead"
                    type="number"
                    label="Срок поставки, дн."
                    hint="обязательно"
                    persistent-hint
                    style="max-width: 180px"
                />
                <v-text-field
                    v-model.number="period1"
                    type="number"
                    label="Окно скрининга, дн."
                    style="max-width: 180px"
                />
                <v-text-field
                    v-model.number="period2"
                    type="number"
                    label="Окно спроса, дн."
                    style="max-width: 180px"
                />
                <v-text-field
                    v-model.number="minSales"
                    type="number"
                    label="Продаж больше, чем"
                    style="max-width: 180px"
                />
            </div>
        </v-card-text>
        <v-card-actions>
            <v-btn color="primary" :disabled="!lead" :loading="loading" @click="load">
                <v-icon left>mdi-magnify</v-icon>
                Показать
            </v-btn>
            <v-spacer/>
            <v-btn color="success" :disabled="!report" :loading="saving" @click="save">
                <v-icon left>mdi-microsoft-excel</v-icon>
                Выгрузить Excel
            </v-btn>
        </v-card-actions>

        <div v-if="report" class="px-4 pb-2 text--secondary">
            Скрининг {{ report.window.from }} … {{ report.window.to }} ({{ report.window.days }} дн.):
            прошли по продажам {{ report.screened }} → остаток < продаж {{ report.candidates }} →
            к заказу {{ report.rows.length }}
        </div>

        <v-data-table
            v-if="report"
            :headers="headers"
            :items="report.rows"
            hide-default-footer
            disable-pagination
            :items-per-page="-1"
            dense
        >
            <template v-slot:item.name="{ item }">
                <good-name :value="{ GOODSCODE: item.GOODSCODE, name: { NAME: item.name } }" :prim="false"/>
            </template>
            <template v-slot:item.actions="{ item }">
                <v-btn small color="primary" outlined @click="calc(item)">
                    <v-icon left small>mdi-calculator-variant</v-icon>
                    Посчитать индивидуально
                </v-btn>
            </template>
        </v-data-table>
    </div>
</template>

<script>
    import GoodName from "../good/GoodName";

    export default {
        name: "ReplenishList",
        components: {GoodName},
        data: () => ({
            lead: null,
            period1: 30,
            period2: 180,
            minSales: 2,
            loading: false,
            saving: false,
            report: null,
            headers: [
                {text: 'Код', value: 'GOODSCODE'},
                {text: 'Название', value: 'name'},
                {text: 'Корпус', value: 'body'},
                {text: 'Производитель', value: 'producer'},
                {text: 'Продано', value: 'soldPeriod1', align: 'end'},
                {text: 'Заказать', value: 'toOrder', align: 'end'},
                {text: '', value: 'actions', sortable: false, align: 'end'},
            ],
        }),
        methods: {
            params() {
                return {
                    lead: this.lead,
                    period1: this.period1,
                    period2: this.period2,
                    min_sales: this.minSales,
                };
            },
            load() {
                if (!this.lead) return;
                this.loading = true;
                this.$store.dispatch('REPLENISH/LIST', this.params())
                    .then(data => this.report = data)
                    .catch(() => {})
                    .then(() => this.loading = false);
            },
            save() {
                if (!this.report) return;
                this.saving = true;
                this.$store.dispatch('REPLENISH/SAVE_LIST', {...this.params(), filename: 'Что закупить.xlsx'})
                    .catch(() => {})
                    .then(() => this.saving = false);
            },
            // Перекинуть позицию во вкладку «Индивидуально» вместе с текущим сроком поставки.
            calc(item) {
                this.$emit('calc', {code: item.GOODSCODE, lead: this.lead});
            },
        },
    }
</script>

<style scoped>

</style>
