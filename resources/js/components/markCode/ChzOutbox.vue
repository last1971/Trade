<template>
    <v-card flat>
        <v-card-title class="subtitle-1 py-2">
            Отправка в Честный знак
            <v-spacer/>
            <span v-if="!enabled" class="caption red--text mr-4">
                Очередь на этой базе выключена (MARKING_CHZ_OUTBOX)
            </span>
            <v-btn small icon :loading="loading" title="Обновить" @click="updateItems">
                <v-icon>mdi-refresh</v-icon>
            </v-btn>
        </v-card-title>
        <v-divider/>
        <v-data-table
            :headers="headers"
            :items="items"
            :loading="loading"
            :loading-text="loadingText"
            :options.sync="options"
            :server-items-length="total"
            :expanded.sync="expanded"
            :footer-props="{showFirstLastPage: true}"
            item-key="ID"
            show-expand
            dense
            @item-expanded="loadCodes"
        >
            <!-- Фильтры строкой под шапкой: вид и статус списками, документы — числом -->
            <template v-slot:body.prepend="{ isMobile }">
                <tr v-if="!isMobile">
                    <td/>
                    <td/>
                    <td>
                        <v-select
                            v-model="options.filterValues[0]"
                            :items="kindItems"
                            clearable
                            dense
                            hide-details
                            multiple
                            label="все"
                        />
                    </td>
                    <td>
                        <v-select
                            v-model="options.filterValues[1]"
                            :items="statusItems"
                            clearable
                            dense
                            hide-details
                            multiple
                            label="все"
                        />
                    </td>
                    <td/>
                    <td>
                        <v-text-field v-model="options.filterValues[2]" dense hide-details label="номер"/>
                    </td>
                    <td>
                        <v-text-field v-model="options.filterValues[3]" dense hide-details label="номер"/>
                    </td>
                    <td/>
                    <td/>
                    <td/>
                    <td>
                        <v-text-field v-model="options.filterValues[4]" dense hide-details label="содержит"/>
                    </td>
                    <td/>
                    <td/>
                </tr>
            </template>

            <template v-slot:item.KIND="{ item }">{{ kindText(item.KIND) }}</template>
            <template v-slot:item.STATUS="{ item }">
                <v-chip x-small dark :color="statusColor(item.STATUS)">{{ statusText(item.STATUS) }}</v-chip>
            </template>
            <template v-slot:item.invoice.NS="{ item }">
                <router-link v-if="item.SCODE" :to="{ name: 'invoice', params: { id: item.SCODE } }">
                    {{ (item.invoice && item.invoice.NS) || item.SCODE }}
                </router-link>
            </template>
            <template v-slot:item.transferOut.NSF="{ item }">
                <router-link v-if="item.SFCODE" :to="{ name: 'transfer-out', params: { id: item.SFCODE } }">
                    {{ (item.transferOut && item.transferOut.NSF) || item.SFCODE }}
                </router-link>
            </template>
            <template v-slot:item.CREATED_AT="{ item }">{{ item.CREATED_AT | datetime }}</template>
            <template v-slot:item.SENT_AT="{ item }">{{ item.SENT_AT | datetime }}</template>
            <template v-slot:item.CONFIRMED_AT="{ item }">{{ item.CONFIRMED_AT | datetime }}</template>
            <!-- у нанесения и деления это отчёт СУЗ, у ввода и вывода — документ ГИС МТ -->
            <template v-slot:item.DOC_UUID="{ item }">
                <span class="mono">{{ item.REPORT_ID || item.DOC_UUID }}</span>
            </template>
            <template v-slot:item.actions="{ item }">
                <v-btn
                    v-if="item.STATUS === 'ERROR'"
                    x-small
                    color="primary"
                    :loading="busy === item.ID"
                    @click="retry(item)"
                >
                    Повторить
                </v-btn>
                <!-- Отбитую пачку целиком снимают с отправки, когда ЧЗ не даёт вывести её коды -->
                <v-btn
                    v-if="item.STATUS === 'ERROR'"
                    x-small
                    text
                    class="ml-1"
                    :loading="busy === item.ID"
                    @click="askSkip(item, null)"
                >
                    Не выводить
                </v-btn>
            </template>
            <template v-slot:expanded-item="{ headers: h, item }">
                <td :colspan="h.length" class="py-2">
                    <div v-if="item.ERROR_TEXT" class="red--text mb-2">{{ item.ERROR_TEXT }}</div>
                    <div v-if="!codes[item.ID]" class="grey--text">Коды загружаются…</div>
                    <v-simple-table v-else dense class="codes">
                        <template v-slot:default>
                            <tbody>
                            <tr v-for="code in codes[item.ID]" :key="code.ki">
                                <td class="mono">
                                    <router-link v-if="code.markcode"
                                                 :to="{ name: 'mark-code', params: { id: code.markcode } }"
                                    >{{ code.ki }}</router-link>
                                    <span v-else>{{ code.ki }}</span>
                                </td>
                                <td class="text-right">{{ code.quantity }}</td>
                                <td>
                                    <router-link v-if="code.goodscode"
                                                 :to="{ name: 'good', params: { id: code.goodscode } }"
                                    >{{ code.name || code.goodscode }}</router-link>
                                    <span v-else class="grey--text">кода нет в базе</span>
                                </td>
                                <!-- Снятый код остаётся в пачке: видно, почему он не уехал, и можно вернуть -->
                                <td class="text-right">
                                    <template v-if="code.skipAt">
                                        <span class="grey--text mr-2">не выводим: {{ code.skipText }}</span>
                                        <v-btn x-small text @click="unskip(item, code)">Вернуть</v-btn>
                                    </template>
                                    <v-btn v-else x-small text @click="askSkip(item, code)">Не выводить</v-btn>
                                </td>
                            </tr>
                            </tbody>
                        </template>
                    </v-simple-table>
                </td>
            </template>
        </v-data-table>
        <!-- Причина обязательна: через месяц никто не вспомнит, почему код брошен -->
        <v-dialog v-model="skipDialog" max-width="480">
            <v-card>
                <v-card-title class="subtitle-1">
                    {{ skipCode ? 'Не выводить этот код' : 'Не выводить коды пачки' }}
                </v-card-title>
                <v-card-text>
                    <div class="mono mb-2" v-if="skipCode">{{ skipCode.ki }}</div>
                    <div class="grey--text mb-2" v-else>Кодов в пачке: {{ skipBatch ? skipBatch.CNT : 0 }}</div>
                    <v-text-field
                        v-model="skipReason"
                        label="Почему не выводим"
                        placeholder="Честный знак считает код чужим"
                        autofocus
                        dense
                        @keyup.enter="skip"
                    />
                </v-card-text>
                <v-card-actions>
                    <v-spacer/>
                    <v-btn text @click="skipDialog = false">Отмена</v-btn>
                    <v-btn color="primary" :disabled="!skipReason" :loading="busy === 'skip'" @click="skip">
                        Снять с отправки
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-card>
</template>

<script>
import moment from "moment";
import tableMixin from "../../mixins/tableMixin";
import tableOptionsRouteMixin from "../../mixins/tableOptionsRouteMixin";

// Вид пачки и её статус приходят из базы как есть — здесь только слова для человека.
const KIND = {
    division: 'деление',
    apply: 'нанесение',
    intro: 'ввод в оборот',
    retire: 'вывод из оборота',
    retire_upd: 'вывод по УПД',
    return: 'возврат в оборот',
};
// value колонки → имя для сортировки на сервере: с джойнами голое имя неоднозначно.
const SORT_MAP = {
    'ID': 'CHZ_BATCH.ID',
    'KIND': 'CHZ_BATCH.KIND',
    'STATUS': 'CHZ_BATCH.STATUS',
    'CNT': 'CHZ_BATCH.CNT',
    'CREATED_AT': 'CHZ_BATCH.CREATED_AT',
    'SENT_AT': 'CHZ_BATCH.SENT_AT',
    'CONFIRMED_AT': 'CHZ_BATCH.CONFIRMED_AT',
    'DOC_UUID': 'CHZ_BATCH.DOC_UUID',
    'CREATED_BY': 'CHZ_BATCH.CREATED_BY',
};

const STATUS = {
    READY: ['ждёт отправки', 'grey'],
    WAIT: ['ждёт нанесения', 'grey'],
    SENT: ['отправлена, ждём ЧЗ', 'orange'],
    DONE: ['принято', 'green'],
    ERROR: ['отказ', 'red'],
};

/**
 * Что уехало в Честный знак и чем кончилось. Своего журнала у страницы нет:
 * это те же пачки CHZ_BATCH, которыми живёт воркер chz:outbox.
 * Пачки без статуса (ручные выгрузки файлом) сюда не попадают — их отсекает
 * ChzBatchService.
 */
export default {
    name: "ChzOutbox",
    mixins: [tableMixin, tableOptionsRouteMixin],
    filters: {
        datetime: (v) => v ? moment(v).format('DD.MM.YYYY HH:mm') : '',
    },
    data() {
        return {
            model: 'CHZ-BATCH',
            options: {
                with: ['invoice', 'transferOut'],
                // Имена с таблицей: после джойна S и SF у Firebird иначе
                // «Ambiguous field name» — STATUS есть и там, и там.
                filterAttributes: [
                    'CHZ_BATCH.KIND', 'CHZ_BATCH.STATUS', 'invoice.NS',
                    'transferOut.NSF', 'CHZ_BATCH.DOC_UUID',
                ],
                filterOperators: ['IN', 'IN', '=', '=', 'CONTAIN'],
                filterValues: [[], [], '', '', ''],
                sortBy: ['ID'],
                sortDesc: [true],
            },
            enabled: true,
            codes: {},
            expanded: [],
            busy: null,
            skipDialog: false,
            skipBatch: null,
            skipCode: null,
            skipReason: '',
            kindItems: Object.entries(KIND).map(([value, text]) => ({value, text})),
            statusItems: Object.entries(STATUS).map(([value, [text]]) => ({value, text})),
        };
    },
    created() {
        axios.get('/api/chz/outbox/state')
            .then(({data}) => this.enabled = data.enabled)
            .catch(() => undefined);
    },
    methods: {
        requestParams() {
            return {
                ...this.options,
                sortBy: (this.options.sortBy || []).map((v) => SORT_MAP[v] || v),
            };
        },
        kindText: (kind) => KIND[String(kind || '').trim()] || kind,
        statusText: (status) => (STATUS[String(status || '').trim()] || [status])[0],
        statusColor: (status) => (STATUS[String(status || '').trim()] || [null, 'grey'])[1],
        loadCodes({item, value}) {
            if (!value || this.codes[item.ID]) return;
            axios.get('/api/chz/outbox/' + item.ID + '/codes')
                .then(({data}) => this.$set(this.codes, item.ID, data.codes))
                .catch(this.error);
        },
        retry(item) {
            this.busy = item.ID;
            axios.post('/api/chz/outbox/' + item.ID + '/retry')
                .then(({data}) => {
                    this.$store.commit('SNACKBAR/SUCCESS',
                        `Пачка №${data.id} создана заново, кодов ${data.cnt}`, {root: true});
                    this.updateItems();
                })
                .catch(this.error)
                .then(() => this.busy = null);
        },
        // code = null — снимаем всю пачку.
        askSkip(item, code) {
            this.skipBatch = item;
            this.skipCode = code;
            this.skipReason = code ? '' : (item.ERROR_TEXT || '');
            this.skipDialog = true;
        },
        skip() {
            const body = {reason: this.skipReason};
            if (this.skipCode) body.ki = this.skipCode.ki;
            this.busy = 'skip';
            axios.post('/api/chz/outbox/' + this.skipBatch.ID + '/skip', body)
                .then(({data}) => {
                    this.$store.commit('SNACKBAR/SUCCESS',
                        `Снято с отправки кодов: ${data.skipped}`, {root: true});
                    this.skipDialog = false;
                    this.reloadCodes(this.skipBatch);
                })
                .catch(this.error)
                .then(() => this.busy = null);
        },
        unskip(item, code) {
            this.busy = 'skip';
            axios.post('/api/chz/outbox/' + item.ID + '/unskip', {ki: code.ki})
                .then(() => {
                    this.$store.commit('SNACKBAR/SUCCESS', 'Код вернулся в очередь', {root: true});
                    this.reloadCodes(item);
                })
                .catch(this.error)
                .then(() => this.busy = null);
        },
        // Пометки живут на кодах, а не на пачке: после правки список надо перечитать.
        reloadCodes(item) {
            this.$delete(this.codes, item.ID);
            this.loadCodes({item, value: true});
        },
        error(e) {
            const data = e.response ? e.response.data : {};
            this.$store.commit('SNACKBAR/ERROR', data.message || 'Ошибка запроса', {root: true});
        },
    },
    beforeRouteEnter(to, from, next) {
        next(vm => {
            // with — часть кода, а не настройка: протухший список из URL не должен
            // отключить подгрузку номеров счёта и УПД
            vm.options.with = ['invoice', 'transferOut'];
            vm.$store.commit('BREADCRUMBS/SET', [
                {text: 'Торговля', to: {name: 'home'}, exact: true, disabled: false},
                {text: 'Отправка в ЧЗ', to: {name: 'chz-outbox'}, exact: true, disabled: true},
            ]);
        });
    },
};
</script>

<style scoped>
.mono {
    font-family: monospace;
    font-size: 12px;
}

.codes {
    max-height: 240px;
    overflow-y: auto;
}

.codes .mono {
    word-break: break-all;
}
</style>
