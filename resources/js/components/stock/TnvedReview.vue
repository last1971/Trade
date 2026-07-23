<template>
    <v-container fluid>
        <v-card>
            <v-card-title>Подбор ТН ВЭД — на проверку</v-card-title>
            <v-card-text class="d-flex flex-wrap align-center py-2" style="gap: 16px">
                <v-text-field v-model.number="limit" label="Сколько" type="number"
                              dense hide-details style="max-width: 110px"/>
                <v-text-field v-model.number="confidence" label="Порог %" type="number"
                              dense hide-details style="max-width: 110px"/>
                <v-btn color="primary" outlined :loading="running" @click="run">
                    <v-icon left>mdi-magnify</v-icon>
                    Подобрать пачкой
                </v-btn>
                <span v-if="running" class="text--secondary">идёт подбор… (ИИ, может занять минуты)</span>
                <span v-else-if="updatedAt" class="text--secondary">подобрано: {{ updatedAt }}</span>
                <v-spacer/>
                <v-btn text small :disabled="!items.length" @click="selected = items.slice()">Отметить все</v-btn>
                <v-btn text small :disabled="!selected.length" @click="selected = []">Снять</v-btn>
                <v-btn color="success" :disabled="!selected.length" :loading="applying" @click="apply">
                    Применить выбранные ({{ selected.length }})
                </v-btn>
            </v-card-text>
            <v-alert v-if="!items.length && !loading && !running" type="info" text class="mx-4">
                Пусто. Нажмите «Подобрать пачкой» — ИИ подберёт коды для товаров «не проверяли».
            </v-alert>
            <v-data-table
                v-model="selected"
                :headers="headers"
                :items="items"
                :loading="loading"
                item-key="goodscode"
                show-select
                dense
                :footer-props="{'items-per-page-options': [25, 50, 100, -1]}"
            >
                <template v-slot:item.goodscode="{ item }">
                    <good-info-modal :value="item.goodscode" :text="item.goodscode" plain/>
                </template>
                <template v-slot:item.tnved="{ item }">
                    <v-text-field v-model="item.tnved" dense hide-details style="min-width: 130px"
                                  @change="onTnvedChange(item)"/>
                </template>
                <template v-slot:item.tnved_name="{ item }">
                    <span class="caption">{{ item.tnved_name | shorten }}</span>
                </template>
                <template v-slot:item.mark_required="{ item }">
                    <v-chip small outlined :color="item.mark_required ? 'orange' : 'green'">
                        {{ item.mark_required ? 'подлежит' : 'не подлежит' }}
                    </v-chip>
                </template>
                <template v-slot:item.okpd2="{ item }">
                    <v-select v-if="item.mark_required"
                              v-model="item.okpd2"
                              :items="okpd2ItemsFor(item.tnved)"
                              dense hide-details clearable style="min-width: 150px"/>
                    <span v-else>—</span>
                </template>
                <template v-slot:item.confidence="{ item }">
                    <v-chip small outlined :color="item.confidence >= confidence ? 'green' : 'orange'">
                        {{ item.confidence }}%
                    </v-chip>
                </template>
            </v-data-table>
        </v-card>
    </v-container>
</template>

<script>
import GoodInfoModal from "../good/GoodInfoModal";

export default {
    name: "TnvedReview",
    components: {GoodInfoModal},
    filters: {
        shorten: value => value && value.length > 60 ? value.slice(0, 60) + '…' : (value || ''),
    },
    data: () => ({
        items: [],
        selected: [],
        loading: false,
        running: false,
        applying: false,
        updatedAt: null,
        timer: null,
        limit: 50,
        confidence: 80,
        headers: [
            {text: 'Код', value: 'goodscode'},
            {text: 'Товар', value: 'name'},
            {text: 'ТНВЭД', value: 'tnved', sortable: false},
            {text: 'Что это', value: 'tnved_name', sortable: false},
            {text: 'Подлежит', value: 'mark_required'},
            {text: 'ОКПД2', value: 'okpd2', sortable: false},
            {text: 'Увер.', value: 'confidence', align: 'end'},
        ],
    }),
    beforeRouteEnter(to, from, next) {
        next(vm => vm.$store.commit('BREADCRUMBS/SET', [
            {text: 'Торговля', to: {name: 'home'}, exact: true},
            {text: 'Разгребание склада', to: {name: 'stock-classif'}, exact: true},
            {text: 'Подбор ТН ВЭД', to: {name: 'tnved-review'}, exact: true},
        ]));
    },
    created() {
        // Справочник для селекта ОКПД2 и решения маркируемости (общий стор).
        this.$store.dispatch('MARKING/FETCH');
        this.checkStatus();
        this.load();
    },
    beforeDestroy() {
        clearInterval(this.timer);
    },
    methods: {
        load() {
            this.loading = true;
            axios.get('/api/tnved-suggestions')
                .then(({data}) => this.items = data)
                .catch(() => {})
                .then(() => this.loading = false);
        },
        checkStatus() {
            axios.get('/api/tnved-suggestions/status').then(({data}) => {
                this.updatedAt = data.updated_at;
                if (data.running) {
                    this.running = true;
                    this.poll();
                }
            });
        },
        run() {
            this.running = true;
            axios.post('/api/tnved-suggestions/run', {limit: this.limit, confidence: this.confidence})
                .then(() => this.poll())
                .catch((e) => {
                    this.running = false;
                    this.$store.commit('SNACKBAR/ERROR',
                        e.response?.data?.message || 'Не удалось запустить подбор', {root: true});
                });
        },
        poll() {
            clearInterval(this.timer);
            this.timer = setInterval(() => {
                axios.get('/api/tnved-suggestions/status').then(({data}) => {
                    if (!data.running) {
                        clearInterval(this.timer);
                        this.running = false;
                        this.updatedAt = data.updated_at;
                        this.load();
                    }
                });
            }, 5000);
        },
        // Правка кода вручную: пересчитать маркируемость/ОКПД2 и обновить «что это».
        onTnvedChange(item) {
            item.mark_required = this.$store.getters['MARKING/IS_MARK_REQUIRED'](item.tnved) ? 1 : 0;
            const opts = this.$store.getters['MARKING/OKPD2_OPTIONS'](item.tnved).map(o => o.c);
            if (!opts.includes(item.okpd2)) {
                item.okpd2 = opts.length === 1 ? opts[0] : '';
            }
            if (/^\d+$/.test(item.tnved)) {
                axios.get('/api/tnved/' + item.tnved)
                    .then(({data}) => item.tnved_name = data.name)
                    .catch(() => {});
            }
        },
        okpd2ItemsFor(code) {
            return this.$store.getters['MARKING/OKPD2_OPTIONS'](code)
                .map(o => ({text: o.c + ' — ' + o.n, value: o.c}));
        },
        apply() {
            this.applying = true;
            axios.post('/api/tnved-suggestions/apply', {
                items: this.selected.map(i => ({
                    GOODSCODE: i.goodscode,
                    MARK_REQUIRED: i.mark_required ? 1 : 0,
                    TNVED: i.tnved || null,
                    OKPD2: i.okpd2 || null,
                    PRIM: ('авто-подбор ИИ, уверенность ' + i.confidence + '%, модель ' + (i.model || '')).slice(0, 250),
                })),
            })
                .then(({data}) => {
                    const msg = 'Применено: ' + data.applied
                        + (data.errors.length ? ', ошибок: ' + data.errors.length : '');
                    this.$store.commit('SNACKBAR/PUSH',
                        {text: msg, color: data.errors.length ? 'warning' : 'success', status: true},
                        {root: true});
                    this.selected = [];
                    this.load();
                })
                .catch((e) => {
                    this.$store.commit('SNACKBAR/ERROR',
                        e.response?.data?.message || 'Ошибка применения', {root: true});
                })
                .then(() => this.applying = false);
        },
    },
}
</script>

<style scoped>
</style>
