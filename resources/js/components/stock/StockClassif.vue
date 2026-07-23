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
                <v-select
                    v-model="category"
                    :items="categoryItems"
                    label="Категория"
                    dense hide-details clearable
                    style="max-width: 220px"
                />
                <v-select
                    v-if="subcategoryItems.length"
                    v-model="subcategory"
                    :items="subcategoryItems"
                    label="Подкатегория"
                    dense hide-details clearable
                    style="max-width: 220px"
                />
                <v-select
                    v-model="status"
                    :items="statusItems"
                    label="Разбор"
                    dense hide-details
                    style="max-width: 170px"
                />
                <v-spacer/>
                <span v-if="updatedAt" class="text--secondary">данные на {{ updatedAt }}</span>
                <v-btn color="secondary" outlined :to="{name: 'tnved-review'}">
                    <v-icon left>mdi-magnify</v-icon>
                    Подбор ТН ВЭД (ИИ)
                </v-btn>
                <v-btn color="primary" outlined :loading="running" @click="refresh">
                    <v-icon left>mdi-refresh</v-icon>
                    Обновить данные
                </v-btn>
            </v-card-text>
            <v-alert v-if="!updatedAt && !running" type="info" text class="mx-4">
                Данные ещё не считались — нажмите «Обновить данные» (расчёт ~3 минуты).
            </v-alert>
            <v-data-table
                v-model="checked"
                :headers="headers"
                :items="items"
                :options.sync="options"
                :server-items-length="total"
                :loading="loading"
                :footer-props="{'items-per-page-options': [25, 50, 100]}"
                item-key="GOODSCODE"
                show-select
                dense
                @click:row="open"
            >
                <!-- Массовый разбор: галочки только на «не проверяли». -->
                <template v-slot:header.data-table-select>
                    <span class="text--secondary caption">разбор</span>
                </template>
                <template v-slot:item.data-table-select="{ item, isSelected, select }">
                    <v-simple-checkbox
                        v-if="!item.classifs.length"
                        :value="isSelected"
                        :ripple="false"
                        @input="select($event)"
                        @click.stop
                    />
                </template>
                <template v-slot:top>
                    <div class="d-flex flex-wrap align-center px-4 py-2" style="gap: 12px">
                        <v-btn small text color="primary" :disabled="!unclassifiedItems.length"
                               @click="selectAllUnclassified">
                            Выбрать неразобранные ({{ unclassifiedItems.length }})
                        </v-btn>
                        <v-btn small text :disabled="!checked.length" @click="checked = []">
                            Снять
                        </v-btn>
                        <template v-if="checked.length">
                            <v-divider vertical/>
                            <span class="text--secondary">выбрано: {{ checked.length }}</span>
                            <verdict-picker v-model="bulkVerdict"/>
                            <v-btn small color="primary" :loading="bulkSaving"
                                   :disabled="!bulkVerdict.tnved"
                                   @click="classifySelected">
                                Классифицировать выбранные ({{ checked.length }})
                            </v-btn>
                        </template>
                    </div>
                </template>
                <template v-slot:item.GOODSCODE="{ item }">
                    <span>{{ item.GOODSCODE }}</span>
                    <v-icon x-small class="copy-ico ml-1" title="Скопировать код"
                            @click.stop="copy(item.GOODSCODE)">mdi-content-copy</v-icon>
                </template>
                <template v-slot:item.NAME="{ item }">
                    <good-name :value="{ GOODSCODE: item.GOODSCODE, name: { NAME: item.NAME } }" :prim="false"/>
                    <v-icon x-small class="copy-ico ml-1" title="Скопировать название"
                            @click.stop="copy(item.NAME)">mdi-content-copy</v-icon>
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
import VerdictPicker from "../good/VerdictPicker";
import marking from "../../mixins/marking";
import _ from "lodash";

export default {
    name: "StockClassif",
    components: {GoodName, GoodInfoModal, VerdictPicker},
    mixins: [marking],
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
        category: 0,
        subcategory: 0,
        status: 'all',
        categories: [],
        statusItems: [
            {text: 'Все', value: 'all'},
            {text: 'Разобранные', value: 'checked'},
            {text: 'Неразобранные', value: 'unchecked'},
        ],
        options: {page: 1, itemsPerPage: 25},
        selected: null,
        modal: false,
        timer: null,
        // Массовый разбор «не проверяли»: выбор строк + значения вердикта.
        checked: [],
        bulkVerdict: {tnved: '', okpd2: ''},
        bulkSaving: false,
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
        if (query.cat) this.category = Number(query.cat);
        if (query.sub) this.subcategory = Number(query.sub);
        if (query.status) this.status = query.status;
        if (query.page > 1) this.options.page = Number(query.page);
        if (query.ipp) this.options.itemsPerPage = Number(query.ipp);
        // Если пересчёт уже идёт (cron или другой пользователь) — сразу поллим.
        this.$store.dispatch('STOCK-CLASSIF/STATUS').then((status) => {
            this.running = status.running;
            this.updatedAt = status.updated_at;
            if (status.running) this.poll();
        });
        // Справочник маркировки для тулбара массового разбора (общий стор).
        this.$store.dispatch('MARKING/FETCH');
        // Категории склада для фильтра — один раз.
        this.$store.dispatch('STOCK-CLASSIF/CATEGORIES').then((data) => this.categories = data);
    },
    beforeDestroy() {
        clearInterval(this.timer);
    },
    computed: {
        // Строки «не проверяли» на текущей странице — только их можно разбирать.
        unclassifiedItems() {
            return this.items.filter(item => !item.classifs.length);
        },
        categoryItems() {
            return this.categories.map(c => ({text: c.name, value: c.code}));
        },
        // Подкатегории выбранной категории (пусто — селект скрыт).
        subcategoryItems() {
            const cat = this.categories.find(c => c.code === this.category);
            return cat ? cat.subcategories.map(s => ({text: s.name, value: s.id})) : [];
        },
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
        category() {
            this.subcategory = 0; // сменили категорию — подкатегория неактуальна
            this.resetAndLoad();
        },
        subcategory() {
            this.resetAndLoad();
        },
        status() {
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
                category: this.category || 0,
                subcategory: this.subcategory || 0,
                status: this.status,
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
            if (this.category) query.cat = String(this.category);
            if (this.subcategory) query.sub = String(this.subcategory);
            if (this.status !== 'all') query.status = this.status;
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
            // Пересчёт идёт ~3 мин; ждём максимум 5, иначе снимаем индикатор:
            // пересчёт мог умереть, оставив флаг (он протухнет сам), а крутить
            // спиннер бесконечно и молчать при ошибке статуса нельзя.
            let attempts = 0;
            this.timer = setInterval(() => {
                if (++attempts > 60) {
                    this.stopPolling();
                    this.$store.commit('SNACKBAR/ERROR',
                        'Пересчёт не завершился за 5 минут — проверьте лог сервера', {root: true});
                    return;
                }
                this.$store.dispatch('STOCK-CLASSIF/STATUS')
                    .then((status) => {
                        if (!status.running) {
                            this.updatedAt = status.updated_at;
                            this.stopPolling();
                            this.load();
                        }
                    })
                    .catch(() => this.stopPolling());
            }, 5000);
        },
        stopPolling() {
            clearInterval(this.timer);
            this.timer = null;
            this.running = false;
        },
        open(item) {
            this.selected = item.GOODSCODE;
            this.modal = true;
        },
        // Копирование в буфер; на http navigator.clipboard недоступен — фолбэк.
        copy(text) {
            const value = String(text);
            const done = () => this.$store.commit('SNACKBAR/PUSH',
                {text: 'Скопировано: ' + value, color: 'success', status: true}, {root: true});
            if (navigator.clipboard) {
                navigator.clipboard.writeText(value).then(done).catch(() => this.copyFallback(value, done));
            } else {
                this.copyFallback(value, done);
            }
        },
        copyFallback(value, done) {
            const el = document.createElement('textarea');
            el.value = value;
            el.style.position = 'fixed';
            el.style.opacity = '0';
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
            done();
        },
        // Выбрать все неразобранные строки текущей страницы.
        selectAllUnclassified() {
            this.checked = [...this.unclassifiedItems];
        },
        // Массовый вердикт: одинаковые значения на выбранные товары (classify-bulk).
        classifySelected() {
            if (!this.checked.length || !this.bulkVerdict.tnved) return;
            this.bulkSaving = true;
            axios.post('/api/good/classify-bulk', {
                GOODSCODES: this.checked.map(item => item.GOODSCODE),
                MARK_REQUIRED: this.isMarkRequired(this.bulkVerdict.tnved) ? 1 : 0,
                TNVED: this.bulkVerdict.tnved || null,
                OKPD2: this.bulkVerdict.okpd2 || null,
            })
                .then(({data}) => {
                    const msg = 'Классифицировано: ' + data.applied
                        + (data.errors.length ? ', ошибок: ' + data.errors.length : '');
                    this.$store.commit('SNACKBAR/PUSH',
                        {text: msg, color: data.errors.length ? 'warning' : 'success', status: true},
                        {root: true});
                    this.checked = [];
                    this.bulkVerdict = {tnved: '', okpd2: ''};
                    this.load();
                })
                .catch((error) => {
                    this.$store.commit('SNACKBAR/ERROR',
                        error.response?.data?.message || 'Ошибка классификации', {root: true});
                })
                .then(() => this.bulkSaving = false);
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
/* Иконка копирования — приглушена, ярче при наведении на строку. */
.copy-ico {
    opacity: 0.35;
    cursor: pointer;
}
.copy-ico:hover,
::v-deep tbody tr:hover .copy-ico {
    opacity: 1;
}
</style>
