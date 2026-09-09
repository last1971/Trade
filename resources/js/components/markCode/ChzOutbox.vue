<template>
    <v-card flat>
        <v-card-title class="subtitle-1 py-2">
            Отправка в Честный знак
            <v-spacer/>
            <span v-if="!enabled" class="caption red--text mr-4">
                Очередь на этой базе выключена (MARKING_CHZ_OUTBOX)
            </span>
            <v-btn small icon :loading="loading" title="Обновить" @click="load">
                <v-icon>mdi-refresh</v-icon>
            </v-btn>
        </v-card-title>
        <v-divider/>
        <v-data-table
            :headers="headers"
            :items="batches"
            :loading="loading"
            :expanded.sync="expanded"
            item-key="id"
            show-expand
            dense
            :items-per-page="25"
            :footer-props="{showFirstLastPage: true}"
            @item-expanded="loadCodes"
        >
            <template v-slot:item.kind="{ item }">{{ kindText(item.kind) }}</template>
            <template v-slot:item.status="{ item }">
                <v-chip x-small dark :color="statusColor(item.status)">{{ statusText(item.status) }}</v-chip>
            </template>
            <template v-slot:item.scode="{ item }">
                <router-link v-if="item.scode" :to="{ name: 'invoice', params: { id: item.scode } }">
                    {{ item.invoiceNumber || item.scode }}
                </router-link>
                <!-- У вывода по УПД документ не счёт: номер показываем, ссылки на УПД нет -->
                <span v-else-if="item.sfcode">УПД {{ item.docNumber || item.sfcode }}</span>
            </template>
            <template v-slot:item.createdAt="{ item }">{{ item.createdAt | datetime }}</template>
            <template v-slot:item.confirmedAt="{ item }">{{ item.confirmedAt | datetime }}</template>
            <!-- у нанесения и деления это отчёт СУЗ, у ввода в оборот — документ ГИС МТ -->
            <template v-slot:item.reportId="{ item }">
                <span class="mono">{{ item.reportId || item.docUuid }}</span>
            </template>
            <template v-slot:item.actions="{ item }">
                <v-btn
                    v-if="item.status === 'ERROR'"
                    x-small
                    color="primary"
                    :loading="busy === item.id"
                    @click="retry(item)"
                >
                    Повторить
                </v-btn>
                <!-- Отбитую пачку целиком снимают с отправки, когда ЧЗ не даёт вывести её коды -->
                <v-btn
                    v-if="item.status === 'ERROR'"
                    x-small
                    text
                    class="ml-1"
                    :loading="busy === item.id"
                    @click="askSkip(item, null)"
                >
                    Не выводить
                </v-btn>
            </template>
            <template v-slot:expanded-item="{ headers: h, item }">
                <td :colspan="h.length" class="py-2">
                    <div v-if="item.errorText" class="red--text mb-2">{{ item.errorText }}</div>
                    <div v-if="!codes[item.id]" class="grey--text">Коды загружаются…</div>
                    <v-simple-table v-else dense class="codes">
                        <template v-slot:default>
                            <tbody>
                            <tr v-for="code in codes[item.id]" :key="code.ki">
                                <td class="mono">{{ code.ki }}</td>
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
                    <div class="grey--text mb-2" v-else>Кодов в пачке: {{ skipBatch ? skipBatch.cnt : 0 }}</div>
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

const POLL_MS = 30000;

// Вид пачки и её статус приходят из базы как есть — здесь только слова для человека.
const KIND = {
    division: 'деление',
    apply: 'нанесение',
    intro: 'ввод в оборот',
    retire: 'вывод из оборота',
    retire_upd: 'вывод по УПД',
    return: 'возврат в оборот',
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
 * Пачки без статуса (ручные выгрузки файлом) сюда не попадают.
 */
export default {
    name: "ChzOutbox",
    filters: {
        datetime: (v) => v ? moment(v).format('DD.MM.YYYY HH:mm') : '',
    },
    data() {
        return {
            enabled: true,
            batches: [],
            codes: {},
            expanded: [],
            loading: false,
            busy: null,
            timer: null,
            skipDialog: false,
            skipBatch: null,
            skipCode: null,
            skipReason: '',
            headers: [
                {text: '№', value: 'id', width: 80},
                {text: 'Вид', value: 'kind'},
                {text: 'Статус', value: 'status'},
                {text: 'Кодов', value: 'cnt', align: 'end'},
                {text: 'Счёт №', value: 'scode'},
                {text: 'Создана', value: 'createdAt'},
                {text: 'Принято', value: 'confirmedAt'},
                {text: 'Отчёт / документ', value: 'reportId'},
                {text: 'Кем', value: 'createdBy'},
                {text: '', value: 'actions', sortable: false},
            ],
        };
    },
    created() {
        this.load();
    },
    beforeDestroy() {
        clearInterval(this.timer);
    },
    methods: {
        kindText: (kind) => KIND[kind] || kind,
        statusText: (status) => (STATUS[status] || [status])[0],
        statusColor: (status) => (STATUS[status] || [null, 'grey'])[1],
        load() {
            this.loading = true;
            axios.get('/api/chz/outbox')
                .then(({data}) => {
                    this.enabled = data.enabled;
                    this.batches = data.batches;
                    this.schedulePoll();
                })
                .catch(this.error)
                .then(() => this.loading = false);
        },
        // Поллинг только пока есть незавершённые пачки: остальное время страница молчит.
        schedulePoll() {
            clearInterval(this.timer);
            this.timer = null;
            if (!this.batches.some((b) => b.status === 'READY' || b.status === 'WAIT' || b.status === 'SENT')) return;
            this.timer = setInterval(this.load, POLL_MS);
        },
        loadCodes({item, value}) {
            if (!value || this.codes[item.id]) return;
            axios.get('/api/chz/outbox/' + item.id + '/codes')
                .then(({data}) => this.$set(this.codes, item.id, data.codes))
                .catch(this.error);
        },
        retry(item) {
            this.busy = item.id;
            axios.post('/api/chz/outbox/' + item.id + '/retry')
                .then(({data}) => {
                    this.$store.commit('SNACKBAR/SUCCESS',
                        `Пачка №${data.id} создана заново, кодов ${data.cnt}`, {root: true});
                    this.load();
                })
                .catch(this.error)
                .then(() => this.busy = null);
        },
        // code = null — снимаем всю пачку.
        askSkip(item, code) {
            this.skipBatch = item;
            this.skipCode = code;
            this.skipReason = code ? '' : (item.errorText || '');
            this.skipDialog = true;
        },
        skip() {
            const body = {reason: this.skipReason};
            if (this.skipCode) body.ki = this.skipCode.ki;
            this.busy = 'skip';
            axios.post('/api/chz/outbox/' + this.skipBatch.id + '/skip', body)
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
            axios.post('/api/chz/outbox/' + item.id + '/unskip', {ki: code.ki})
                .then(() => {
                    this.$store.commit('SNACKBAR/SUCCESS', 'Код вернулся в очередь', {root: true});
                    this.reloadCodes(item);
                })
                .catch(this.error)
                .then(() => this.busy = null);
        },
        // Пометки живут на кодах, а не на пачке: после правки список надо перечитать.
        reloadCodes(item) {
            this.$delete(this.codes, item.id);
            this.loadCodes({item, value: true});
        },
        error(e) {
            const data = e.response ? e.response.data : {};
            this.$store.commit('SNACKBAR/ERROR', data.message || 'Ошибка запроса', {root: true});
        },
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
