<template>
    <v-container fluid>
        <v-card class="mb-4">
            <v-card-title>Долги покупателя</v-card-title>
            <v-card-text>
                <div class="d-flex flex-wrap align-center" style="gap: 16px">
                    <buyer-select v-model="buyer" style="max-width: 360px"/>
                    <div class="d-flex align-center">
                        <date-picker v-model="from" label="Счета не ранее"/>
                        <v-btn v-if="from" icon small class="ml-2" title="Сбросить дату" @click="from = null">
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                    </div>
                </div>
            </v-card-text>
            <v-card-actions>
                <v-btn
                    color="primary"
                    :disabled="!buyer"
                    :loading="loading"
                    @click="load"
                >
                    <v-icon left>mdi-magnify</v-icon>
                    Показать
                </v-btn>
                <v-spacer/>
                <v-btn
                    color="success"
                    :disabled="!buyer"
                    :loading="saving"
                    @click="save"
                >
                    <v-icon left>mdi-microsoft-excel</v-icon>
                    Выгрузить Excel
                </v-btn>
            </v-card-actions>
        </v-card>

        <v-card v-if="report">
            <v-tabs v-model="tab" align-with-title>
                <v-tab>Счета</v-tab>
                <v-tab>Неоплаченные УПД</v-tab>
                <v-tab>Не едет</v-tab>
            </v-tabs>
            <v-tabs-items v-model="tab">
                <v-tab-item :key="0">
                    <buyer-debt-invoices :invoices="report.invoices" :totals="report.totals"/>
                </v-tab-item>
                <v-tab-item :key="1">
                    <buyer-debt-upds :invoices="report.invoices"/>
                </v-tab-item>
                <v-tab-item :key="2">
                    <buyer-debt-holes :invoices="report.invoices"/>
                </v-tab-item>
            </v-tabs-items>
        </v-card>
    </v-container>
</template>

<script>
    import BuyerSelect from "./BuyerSelect";
    import DatePicker from "./DatePicker";
    import BuyerDebtInvoices from "./buyerDebt/BuyerDebtInvoices";
    import BuyerDebtUpds from "./buyerDebt/BuyerDebtUpds";
    import BuyerDebtHoles from "./buyerDebt/BuyerDebtHoles";
    import moment from "moment";
    import createLocalStorageSync from "../helpers/localStorage";

    const filterStorage = createLocalStorageSync('buyer_debt_filter');

    export default {
        name: "BuyerDebt",
        components: {BuyerSelect, DatePicker, BuyerDebtInvoices, BuyerDebtUpds, BuyerDebtHoles},
        data: () => ({
            buyer: null,
            from: null,
            saving: false,
            loading: false,
            report: null,
            tab: null,
        }),
        created() {
            // Восстанавливаем последний выбранный покупателя и дату.
            const saved = filterStorage.get({});
            this.buyer = saved.buyer || null;
            this.from = saved.from || null;
        },
        watch: {
            buyer() {
                this.persistFilter();
            },
            from() {
                this.persistFilter();
            },
        },
        computed: {
            filename() {
                const item = this.$store.getters['BUYER/GET'](this.buyer);
                const name = ((item && item.SHORTNAME) || this.buyer || '').toString().trim();
                const today = moment().format('DD.MM.YYYY');
                const from = this.from ? ' с ' + moment(this.from).format('DD.MM.YYYY') : '';
                // Чистим символы, недопустимые в имени файла.
                return ('Долги ' + name + from + ' по ' + today + '.xlsx')
                    .replace(/[\\/:*?"<>|]/g, ' ')
                    .replace(/\s+/g, ' ')
                    .trim();
            }
        },
        methods: {
            persistFilter() {
                filterStorage.set({buyer: this.buyer, from: this.from});
            },
            load() {
                if (!this.buyer) return;
                this.loading = true;
                this.$store.dispatch('BUYER-DEBT/REPORT', {
                    buyer: this.buyer,
                    from: this.from || undefined,
                })
                    .then(data => this.report = data)
                    .catch(() => {})
                    .then(() => this.loading = false);
            },
            save() {
                if (!this.buyer) return;
                this.saving = true;
                this.$store.dispatch('BUYER-DEBT/SAVE', {
                    buyer: this.buyer,
                    from: this.from || undefined,
                    filename: this.filename,
                })
                    .catch(() => {})
                    .then(() => this.saving = false);
            }
        }
    }
</script>

<style scoped>

</style>
