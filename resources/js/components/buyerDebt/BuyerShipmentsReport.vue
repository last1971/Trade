<template>
    <div>
        <div class="d-flex flex-wrap align-center pa-4" style="gap: 16px">
            <date-picker v-model="from" label="С"/>
            <date-picker v-model="to" label="По"/>
            <v-btn color="primary" :loading="loading" @click="load">
                <v-icon left>mdi-magnify</v-icon>
                Показать
            </v-btn>
        </div>

        <v-data-table
            :headers="headers"
            :items="rows"
            :loading="loading"
            hide-default-footer
            disable-pagination
            :items-per-page="-1"
            dense
        >
            <template v-slot:item.invoices="{ item }">{{ item.invoices | formatRub }}</template>
            <template v-slot:item.shipped="{ item }">{{ item.shipped | formatRub }}</template>
            <template v-slot:item.paid="{ item }">{{ item.paid | formatRub }}</template>
            <template v-slot:item.debt="{ item }">{{ item.debt | formatRub }}</template>
            <template v-slot:item.actions="{ item }">
                <v-btn x-small text color="primary" @click="openDebt(item)">Долги</v-btn>
                <v-btn x-small text color="primary" @click="openUpds(item)">УПД</v-btn>
                <v-btn x-small text color="primary" @click="openInvoices(item)">Счета</v-btn>
            </template>
            <template v-slot:body.append>
                <tr class="font-weight-bold">
                    <td>Итого ({{ rows.length }})</td>
                    <td class="text-right">{{ totals.invoices | formatRub }}</td>
                    <td class="text-right">{{ totals.shipped | formatRub }}</td>
                    <td class="text-right">{{ totals.updCount }}</td>
                    <td class="text-right">{{ totals.paid | formatRub }}</td>
                    <td class="text-right">{{ totals.debt | formatRub }}</td>
                    <td></td>
                </tr>
            </template>
        </v-data-table>
    </div>
</template>

<script>
    import moment from "moment";
    import DatePicker from "../DatePicker";

    export default {
        name: "BuyerShipmentsReport",
        components: {DatePicker},
        data: () => ({
            from: moment().startOf('month').format('Y-MM-DD'),
            to: moment().format('Y-MM-DD'),
            loading: false,
            rows: [],
            headers: [
                {text: 'Покупатель', value: 'name'},
                {text: 'Сумма счетов', value: 'invoices', align: 'end'},
                {text: 'Сумма отгрузок (УПД)', value: 'shipped', align: 'end'},
                {text: 'Кол-во УПД', value: 'updCount', align: 'end'},
                {text: 'Сумма поступлений', value: 'paid', align: 'end'},
                {text: 'Долг', value: 'debt', align: 'end'},
                {text: '', value: 'actions', sortable: false, align: 'center'},
            ],
        }),
        computed: {
            // Итоги по всем строкам таблицы.
            totals() {
                return this.rows.reduce((acc, r) => ({
                    invoices: acc.invoices + r.invoices,
                    shipped: acc.shipped + r.shipped,
                    updCount: acc.updCount + r.updCount,
                    paid: acc.paid + r.paid,
                    debt: acc.debt + r.debt,
                }), {invoices: 0, shipped: 0, updCount: 0, paid: 0, debt: 0});
            },
        },
        created() {
            // Сразу показываем данные за текущий месяц, чтобы не открывать пустую таблицу.
            this.load();
        },
        methods: {
            load() {
                this.loading = true;
                this.$store.dispatch('BUYER-DEBT/SUMMARY', {
                    from: this.from || undefined,
                    to: this.to || undefined,
                })
                    .then(data => this.rows = data)
                    .catch(() => {})
                    .then(() => this.loading = false);
            },
            // Переключаемся на вкладку «Долги» с этим покупателем и датой «С».
            openDebt(item) {
                this.$emit('open-debt', {buyer: item.POKUPATCODE, from: this.from});
            },
            // Открываем список Исх.УПД, отфильтрованный по покупателю и дате (см. TransferOuts.vue).
            openUpds(item) {
                this.$router.push({
                    name: 'transfer-outs',
                    query: {
                        with: ['buyer', 'employee', 'firm', 'invoice'],
                        aggregateAttributes: ['transferOutLinesCount', 'transferOutLinesSum'],
                        filterAttributes: ['DATA', 'NSF', 'buyer.SHORTNAME', 'transferOutLinesSum', 'firm.FIRMNAME', 'employee.FULLNAME'],
                        filterOperators: ['>=', 'LIKE', 'CONTAIN', '>=', 'CONTAIN', 'CONTAIN'],
                        filterValues: [this.from || '', '', item.name, 0, '', ''],
                        sortBy: ['DATA'],
                        sortDesc: ['true'],
                        page: '1',
                        itemsPerPage: '25',
                    },
                });
            },
            // Открываем список Счетов, отфильтрованный по покупателю и дате (см. Invoices.vue).
            openInvoices(item) {
                this.$router.push({
                    name: 'invoices',
                    query: {
                        with: ['buyer', 'employee', 'firm'],
                        aggregateAttributes: ['invoiceLinesCount', 'invoiceLinesSum', 'cashFlowsSum', 'transferOutLinesSum'],
                        filterAttributes: ['DATA', 'NS', 'invoiceLinesSum', 'STATUS', 'buyer.SHORTNAME', 'employee.FULLNAME', 'firm.FIRMNAME', 'cashFlowsSum', 'transferOutLinesSum', 'S.PRIM', 'IGK', 'NZ'],
                        filterOperators: ['>=', 'LIKE', '>=', 'IN', 'CONTAIN', 'CONTAIN', 'CONTAIN', '>=', '>=', 'CONTAIN', 'CONTAIN', '='],
                        filterValues: [this.from || '', '', 0, '', item.name, '', '', 0, 0, '', '', ''],
                        sortBy: ['DATA'],
                        sortDesc: ['true'],
                        page: '1',
                        itemsPerPage: '25',
                    },
                });
            },
        },
    }
</script>

<style scoped>

</style>
