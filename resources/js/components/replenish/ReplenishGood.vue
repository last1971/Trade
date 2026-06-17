<template>
    <div>
        <v-card-text>
            <div class="d-flex flex-wrap align-center" style="gap: 16px">
                <good-select v-model="good" style="flex: 1 1 480px; min-width: 360px"/>
                <v-text-field
                    v-model.number="codeInput"
                    type="number"
                    label="или код товара"
                    style="max-width: 160px"
                    @keyup.enter="applyCode"
                >
                    <template v-slot:append-outer>
                        <v-btn icon small :disabled="!codeInput" title="Применить код" @click="applyCode">
                            <v-icon>mdi-arrow-right-circle</v-icon>
                        </v-btn>
                    </template>
                </v-text-field>
                <v-text-field
                    v-model.number="lead"
                    type="number"
                    label="Срок поставки, дн."
                    hint="обязательно"
                    persistent-hint
                    style="max-width: 180px"
                />
                <v-text-field
                    v-model.number="period"
                    type="number"
                    label="Окно спроса, дн."
                    style="max-width: 180px"
                />
            </div>
        </v-card-text>
        <v-card-actions>
            <v-btn color="primary" :disabled="!good || !lead" :loading="loading" @click="load">
                <v-icon left>mdi-magnify</v-icon>
                Показать
            </v-btn>
            <v-spacer/>
            <v-btn color="success" :disabled="!report" :loading="saving" @click="save">
                <v-icon left>mdi-microsoft-excel</v-icon>
                Выгрузить Excel
            </v-btn>
        </v-card-actions>

        <template v-if="report">
            <v-card-title class="text-subtitle-1 py-2">
                {{ report.good.name || report.good.GOODSCODE }}
                <span class="text--secondary text-body-2 ml-2">
                    окно {{ report.window.from }} … {{ report.window.to }} ({{ report.window.days }} дн.)
                </span>
            </v-card-title>
            <v-data-table
                :headers="headers"
                :items="rows"
                hide-default-footer
                disable-pagination
                :items-per-page="-1"
                dense
            >
                <template v-slot:item.value="{ item }">
                    <span :class="{ 'font-weight-bold': item.bold }">{{ item.value }}</span>
                </template>
            </v-data-table>
        </template>
    </div>
</template>

<script>
    import GoodSelect from "../good/GoodSelect";

    export default {
        name: "ReplenishGood",
        components: {GoodSelect},
        props: {
            // Позиция, перекинутая из вкладки «Лист»: {code, lead}.
            inject: {type: Object, default: null},
        },
        data: () => ({
            good: null,
            codeInput: null,
            lead: null,
            period: 180,
            loading: false,
            saving: false,
            report: null,
            headers: [
                {text: 'Показатель', value: 'label'},
                {text: 'Значение', value: 'value', align: 'end'},
            ],
        }),
        watch: {
            // Прилетела позиция из «Листа» — подставляем код, при пустом сроке берём из листа, считаем.
            inject(val) {
                if (!val) return;
                if (!this.lead && val.lead) this.lead = val.lead;
                this.good = val.code;
                this.load();
            },
        },
        computed: {
            unit() {
                return (this.report && this.report.good.unit) || 'шт';
            },
            // Показатели как в CLI-отчёте report:replenish.
            rows() {
                const r = this.report;
                if (!r) return [];
                const u = this.unit;
                return [
                    {label: 'Код', value: r.good.GOODSCODE},
                    {label: 'Корпус', value: r.good.body},
                    {label: 'Производитель', value: r.good.producer},
                    {label: 'Продажи опт', value: r.sales.opt + ' ' + u},
                    {label: 'Продажи розница', value: r.sales.retail + ' ' + u},
                    {label: 'Продано всего', value: r.sales.total + ' ' + u},
                    {label: 'Спрос/день (среднее)', value: r.sales.meanPerDay},
                    {label: 'Спрос/день (сглаж., в расчёте)', value: r.sales.smoothedPerDay},
                    {label: 'Интервал поставок, дн.', value: r.supply.intervalDays != null ? r.supply.intervalDays : 'нет истории → берём срок поставки'},
                    {label: 'Срок поставки, дн.', value: r.supply.leadDays},
                    {label: 'Покрыть, дн.', value: r.supply.coverDays},
                    {label: 'На складе', value: r.stock.warehouse + ' ' + u},
                    {label: 'В магазине', value: r.stock.retailStore + ' ' + u},
                    {label: 'В резерве', value: r.stock.reserved + ' ' + u},
                    {label: 'Свободно', value: r.stock.available + ' ' + u},
                    {label: 'В пути (заказано)', value: r.stock.inTransit + ' ' + u},
                    {label: 'Целевой запас', value: r.target + ' ' + u},
                    {label: 'Рекомендуем заказать', value: r.toOrder + ' ' + u, bold: true},
                ];
            },
        },
        methods: {
            applyCode() {
                if (!this.codeInput) return;
                this.good = this.codeInput;
                this.load();
            },
            params() {
                return {good: this.good, lead: this.lead, period: this.period};
            },
            load() {
                if (!this.good || !this.lead) return;
                this.loading = true;
                this.$store.dispatch('REPLENISH/REPORT', this.params())
                    .then(data => this.report = data)
                    .catch(() => {})
                    .then(() => this.loading = false);
            },
            save() {
                if (!this.report) return;
                const name = (this.report.good.name || this.report.good.GOODSCODE).toString().replace(/[\\/:*?"<>|]/g, ' ').trim();
                this.saving = true;
                this.$store.dispatch('REPLENISH/SAVE_REPORT', {...this.params(), filename: 'Закупка ' + name + '.xlsx'})
                    .catch(() => {})
                    .then(() => this.saving = false);
            },
        },
    }
</script>

<style scoped>

</style>
