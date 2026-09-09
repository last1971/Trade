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
            </template>
            <template v-slot:expanded-item="{ headers: h, item }">
                <td :colspan="h.length" class="py-2">
                    <div v-if="item.errorText" class="red--text mb-2">{{ item.errorText }}</div>
                    <div v-if="!codes[item.id]" class="grey--text">Коды загружаются…</div>
                    <div v-else class="mono codes">{{ codes[item.id].join('\n') }}</div>
                </td>
            </template>
        </v-data-table>
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
                .then(({data}) => this.$set(this.codes, item.id, data.kis))
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
    white-space: pre-wrap;
    word-break: break-all;
    max-height: 240px;
    overflow-y: auto;
}
</style>
