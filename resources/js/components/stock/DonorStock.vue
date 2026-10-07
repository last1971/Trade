<template>
    <v-container fluid>
        <v-card>
            <v-card-title>Доноры — подобрано в счетах маркетплейсов</v-card-title>
            <v-card-actions class="flex-wrap px-4" style="gap: 16px">
                <v-btn-toggle v-model="marketplace" mandatory color="primary" @change="load">
                    <v-btn v-for="m in marketplaces" :key="m.value" :value="m.value">{{ m.text }}</v-btn>
                </v-btn-toggle>
                <v-spacer/>
                <v-btn color="success" :disabled="!report || !report.rows.length" :loading="saving" @click="save">
                    <v-icon left>mdi-microsoft-excel</v-icon>
                    Выгрузить Excel
                </v-btn>
            </v-card-actions>

            <div v-if="report" class="px-4 pb-2 text--secondary">
                Счета в статусе «сформирован», только подобранное:
                {{ report.total.goods }} товаров, {{ report.total.quantity }} шт
            </div>

            <v-data-table
                :headers="headers"
                :items="report ? report.rows : []"
                :loading="loading"
                item-key="GOODSCODE"
                hide-default-footer
                disable-pagination
                :items-per-page="-1"
                dense
            >
                <template v-slot:item.name="{ item }">
                    <good-name :value="{ GOODSCODE: item.GOODSCODE, name: { NAME: item.name } }" :prim="false"/>
                </template>
            </v-data-table>
        </v-card>
    </v-container>
</template>

<script>
    import GoodName from "../good/GoodName";

    export default {
        name: "DonorStock",
        components: {GoodName},
        data: () => ({
            marketplace: 'ozon',
            marketplaces: [
                {value: 'ozon', text: 'Озон (Интернет решения)'},
                {value: 'wb', text: 'Вайлдберриз'},
            ],
            loading: false,
            saving: false,
            report: null,
            headers: [
                {text: 'Код', value: 'GOODSCODE'},
                {text: 'Наименование', value: 'name'},
                {text: 'Подобрано, шт', value: 'quantity', align: 'end'},
                {text: 'Счетов', value: 'invoices', align: 'end'},
            ],
        }),
        created() {
            this.load();
        },
        methods: {
            load() {
                this.loading = true;
                this.report = null;
                this.$store.dispatch('DONOR-STOCK/LIST', {marketplace: this.marketplace})
                    .then(data => this.report = data)
                    .catch(() => {})
                    .then(() => this.loading = false);
            },
            save() {
                const label = this.marketplaces.find(m => m.value === this.marketplace).text;
                const filename = 'Доноры ' + label + ' ' + new Date().toLocaleDateString('ru-RU') + '.xlsx';
                this.saving = true;
                this.$store.dispatch('DONOR-STOCK/SAVE_LIST', {marketplace: this.marketplace, filename})
                    .catch(() => {})
                    .then(() => this.saving = false);
            },
        },
    }
</script>
