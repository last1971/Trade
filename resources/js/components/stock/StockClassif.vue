<template>
    <v-container fluid>
        <v-card>
            <v-card-title>Разгребание склада</v-card-title>
            <v-card-text class="d-flex flex-wrap align-center py-0" style="gap: 16px">
                <v-text-field
                    v-model="search"
                    label="Наименование или код"
                    clearable
                    style="max-width: 280px"
                />
                <v-switch v-model="problemMarking" label="Маркировка" dense hide-details/>
                <v-switch v-model="problemNoCert" label="Сертификат" dense hide-details/>
                <v-select
                    v-model="marketplace"
                    :items="marketplaceItems"
                    label="Маркетплейс"
                    dense hide-details
                    style="max-width: 160px"
                />
                <v-spacer/>
                <span v-if="updatedAt" class="text--secondary">данные на {{ updatedAt }}</span>
                <v-btn color="primary" outlined :loading="running" @click="refresh">
                    <v-icon left>mdi-refresh</v-icon>
                    Обновить данные
                </v-btn>
            </v-card-text>
            <v-alert v-if="!updatedAt && !running" type="info" text class="mx-4">
                Данные ещё не считались — нажмите «Обновить данные» (расчёт ~3 минуты).
            </v-alert>
            <v-data-table
                :headers="headers"
                :items="items"
                :options.sync="options"
                :server-items-length="total"
                :loading="loading"
                :footer-props="{'items-per-page-options': [25, 50, 100]}"
                item-key="GOODSCODE"
                dense
                @click:row="open"
            >
                <template v-slot:item.NAME="{ item }">
                    <good-name :value="{ GOODSCODE: item.GOODSCODE, name: { NAME: item.NAME } }" :prim="false"/>
                </template>
                <template v-slot:item.VAL="{ item }">
                    {{ item.VAL | formatRub }}
                </template>
                <template v-slot:item.UNCOVERED="{ item }">
                    <span v-if="item.UNCOVERED > 0" class="orange--text font-weight-bold">
                        {{ item.UNCOVERED }}
                    </span>
                </template>
                <template v-slot:item.marking="{ item }">
                    <v-chip small outlined :color="markingColor(item)">
                        {{ markingText(item) }}
                    </v-chip>
                </template>
                <template v-slot:item.cert="{ item }">
                    <v-chip small outlined :color="item.problem_no_cert ? 'red' : 'green'">
                        {{ item.problem_no_cert ? 'нет' : 'есть' }}
                    </v-chip>
                </template>
                <template v-slot:item.mp="{ item }">
                    <v-chip v-if="item.mp.includes('ozon')" x-small outlined color="blue" class="mr-1">Озон</v-chip>
                    <v-chip v-if="item.mp.includes('wb')" x-small outlined color="purple">ВБ</v-chip>
                </template>
                <template v-slot:item.codes="{ item }">
                    <v-chip v-if="item.CODES > item.OST" small outlined color="red"
                            title="Живых кодов больше остатка — товар ушёл без списания кода">
                        кодов {{ item.CODES }} &gt; {{ item.OST }}
                    </v-chip>
                    <span v-else-if="item.CODES > 0">{{ item.CODES }}</span>
                </template>
            </v-data-table>
        </v-card>
        <good-info-modal
            v-if="selected"
            :value="selected"
            :with-activator="false"
            :is-active.sync="modal"
        />
    </v-container>
</template>

<script>
import GoodName from "../good/GoodName";
import GoodInfoModal from "../good/GoodInfoModal";
import _ from "lodash";

export default {
    name: "StockClassif",
    components: {GoodName, GoodInfoModal},
    data: () => ({
        items: [],
        total: 0,
        updatedAt: null,
        loading: false,
        running: false,
        search: '',
        problemMarking: true,
        problemNoCert: true,
        marketplace: '',
        marketplaceItems: [
            {text: 'Все', value: ''},
            {text: 'Озон', value: 'ozon'},
            {text: 'ВБ', value: 'wb'},
            {text: 'На любом', value: 'any'},
            {text: 'Нет на МП', value: 'none'},
        ],
        options: {page: 1, itemsPerPage: 25},
        selected: null,
        modal: false,
        timer: null,
        headers: [
            {text: 'Код', value: 'GOODSCODE', sortable: false},
            {text: 'Наименование', value: 'NAME', sortable: false},
            {text: 'Остаток', value: 'OST', sortable: false, align: 'end'},
            {text: 'Стоимость', value: 'VAL', sortable: false, align: 'end'},
            {text: 'Непокрыто', value: 'UNCOVERED', sortable: false, align: 'end'},
            {text: 'Маркировка', value: 'marking', sortable: false},
            {text: 'Сертификат', value: 'cert', sortable: false},
            {text: 'МП', value: 'mp', sortable: false},
            {text: 'Коды ЧЗ', value: 'codes', sortable: false},
        ],
    }),
    beforeRouteEnter(to, from, next) {
        next(vm => vm.$store.commit('BREADCRUMBS/SET', [
            {text: 'Торговля', to: {name: 'home'}, exact: true},
            {text: 'Разгребание склада', to: {name: 'stock-classif'}, exact: true},
        ]));
    },
    created() {
        // Восстановление состояния из URL (возврат по крошке с карточки товара).
        const query = this.$route.query;
        if (query.search) this.search = query.search;
        if (query.marking === '0') this.problemMarking = false;
        if (query.cert === '0') this.problemNoCert = false;
        if (query.mp) this.marketplace = query.mp;
        if (query.page > 1) this.options.page = Number(query.page);
        if (query.ipp) this.options.itemsPerPage = Number(query.ipp);
        // Если пересчёт уже идёт (cron или другой пользователь) — сразу поллим.
        this.$store.dispatch('STOCK-CLASSIF/STATUS').then((status) => {
            this.running = status.running;
            this.updatedAt = status.updated_at;
            if (status.running) this.poll();
        });
    },
    beforeDestroy() {
        clearInterval(this.timer);
    },
    watch: {
        options: {
            deep: true,
            handler() {
                this.load();
            },
        },
        search: _.debounce(function () {
            this.resetAndLoad();
        }, 1000),
        problemMarking() {
            this.resetAndLoad();
        },
        problemNoCert() {
            this.resetAndLoad();
        },
        marketplace() {
            this.resetAndLoad();
        },
        // После закрытия карточки перечитать список — вердикт мог убрать товар.
        modal(value) {
            if (!value) this.load();
        },
    },
    methods: {
        params() {
            const problems = [];
            if (this.problemMarking) problems.push('marking');
            if (this.problemNoCert) problems.push('noCert');
            return {
                page: this.options.page,
                itemsPerPage: this.options.itemsPerPage,
                search: this.search || '',
                problems,
                marketplace: this.marketplace,
            };
        },
        // Сброс на первую страницу; если уже на ней — просто перечитать
        // (смену page подхватит watcher options и загрузит сам).
        resetAndLoad() {
            if (this.options.page !== 1) {
                this.options.page = 1;
            } else {
                this.load();
            }
        },
        load() {
            this.loading = true;
            this.syncQuery();
            this.$store.dispatch('STOCK-CLASSIF/LIST', this.params())
                .then((data) => {
                    this.items = data.data;
                    this.total = data.total;
                    this.updatedAt = data.updated_at;
                })
                .catch(() => {})
                .then(() => this.loading = false);
        },
        // Состояние страницы в URL: крошка (BREADCRUMBS/SYNC) вернёт сюда как было.
        syncQuery() {
            const query = {};
            if (this.search) query.search = this.search;
            if (!this.problemMarking) query.marking = '0';
            if (!this.problemNoCert) query.cert = '0';
            if (this.marketplace) query.mp = this.marketplace;
            if (this.options.page > 1) query.page = String(this.options.page);
            if (this.options.itemsPerPage !== 25) query.ipp = String(this.options.itemsPerPage);
            if (!_.isEqual(query, this.$route.query)) {
                this.$router.replace({query}).catch(() => {});
            }
        },
        refresh() {
            this.running = true;
            this.$store.dispatch('STOCK-CLASSIF/REFRESH')
                .then(() => this.poll())
                .catch(() => this.running = false);
        },
        poll() {
            this.running = true;
            clearInterval(this.timer);
            this.timer = setInterval(() => {
                this.$store.dispatch('STOCK-CLASSIF/STATUS').then((status) => {
                    if (!status.running) {
                        clearInterval(this.timer);
                        this.running = false;
                        this.updatedAt = status.updated_at;
                        this.load();
                    }
                });
            }, 5000);
        },
        open(item) {
            this.selected = item.GOODSCODE;
            this.modal = true;
        },
        markingText(item) {
            if (!item.classifs.length) return 'не проверяли';
            return item.classifs.some(row => row.MARK_REQUIRED) ? 'подлежит' : 'не подлежит';
        },
        markingColor(item) {
            if (!item.classifs.length) return 'grey';
            if (!item.classifs.some(row => row.MARK_REQUIRED)) return 'green';
            // подлежит: оранжевый, пока есть непокрытые штуки или кодов меньше остатка
            return (item.UNCOVERED > 0 || item.CODES < item.OST) ? 'orange' : 'green';
        },
    },
}
</script>

<style scoped>
/* Строки кликабельны — открывают карточку товара. */
::v-deep tbody tr {
    cursor: pointer;
}
</style>
