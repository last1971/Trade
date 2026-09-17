<template>
    <div>
        <div class="subtitle-2 mb-2">Заказы КМ · GTIN {{ gtin }}</div>
        <!-- Справка «непокрыто»: один код на партию, партий N → N кодов. Не количество к заказу. -->
        <div class="d-flex align-center flex-wrap mb-2" style="gap: 12px">
            <span v-if="uncovered">
                Непокрыто: <b>{{ uncovered.total }}</b> шт в <b>{{ uncovered.parcels.length }}</b>
                {{ parcelsWord(uncovered.parcels.length) }} ({{ uncovered.mode === 'shop' ? 'магазин' : 'склад' }})
            </span>
            <span v-else class="grey--text">Непокрыто: считаю…</span>
        </div>
        <v-simple-table v-if="uncovered && uncovered.parcels.length" dense class="mb-4">
            <template v-slot:default>
                <thead>
                <tr>
                    <th>Приход</th>
                    <th>Дата</th>
                    <th class="text-right">В партии</th>
                    <th class="text-right">Ушло/резерв</th>
                    <th class="text-right">Под кодами</th>
                    <th class="text-right">Непокрыто</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="p in uncovered.parcels" :key="p.pmid">
                    <td>{{ p.np }}</td>
                    <td>{{ p.date | date }}</td>
                    <td class="text-right">{{ p.quan }}</td>
                    <td class="text-right">{{ p.blocked }}</td>
                    <td class="text-right">{{ p.codes }}</td>
                    <td class="text-right"><b>{{ p.free }}</b></td>
                </tr>
                </tbody>
            </template>
        </v-simple-table>

        <div v-if="canOrder" class="d-flex align-center flex-wrap mb-4" style="gap: 12px">
            <v-text-field
                v-model.number="quantity"
                label="Заказать кодов"
                type="number" min="1" dense hide-details
                style="max-width: 160px"
            />
            <v-btn small color="primary" :loading="ordering" :disabled="!quantity || quantity < 1" @click="order">
                Заказать в СУЗ
            </v-btn>
            <span class="caption grey--text">Эмиссия платная, деньги спишутся сразу</span>
        </div>

        <v-simple-table dense>
            <template v-slot:default>
                <thead>
                <tr>
                    <th>Дата</th>
                    <th>Заказ</th>
                    <th class="text-right">Кодов</th>
                    <th>Статус</th>
                    <th>Буфер</th>
                    <th class="text-right">В буфере</th>
                    <th style="width: 260px"></th>
                </tr>
                </thead>
                <tbody>
                <tr v-if="!orders.length">
                    <td colspan="7" class="text-center">{{ loading ? 'Загрузка…' : 'Заказов нет' }}</td>
                </tr>
                <tr v-for="o in orders" :key="o.orderId">
                    <td>{{ o.createdAt | datetime }}</td>
                    <td :title="o.orderId">{{ o.orderId.slice(0, 8) }}…</td>
                    <td class="text-right">{{ o.quantity }}</td>
                    <td>
                        <v-chip x-small :color="statusColor(o.status)" outlined>{{ statusText(o.status) }}</v-chip>
                    </td>
                    <td>
                        <span v-if="o.rejectionReason" class="red--text">{{ o.rejectionReason }}</span>
                        <span v-else>{{ o.bufferStatus || '—' }}</span>
                    </td>
                    <td class="text-right">{{ o.leftInBuffer === null ? '—' : o.leftInBuffer }}</td>
                    <td>
                        <template v-if="o.fetched">
                            <v-btn x-small outlined color="blue" :loading="busy === o.orderId + ':pdf'" @click="downloadPdf(o)" title="Этикетки PDF, пачками">
                                <v-icon x-small left>mdi-file-pdf-box</v-icon>PDF
                            </v-btn>
                            <v-btn x-small outlined color="green" class="ml-1" :loading="busy === o.orderId + ':csv'" @click="downloadCsv(o)" title="Полные КМ, по одному в строке">
                                <v-icon x-small left>mdi-file-delimited</v-icon>CSV
                            </v-btn>
                        </template>
                        <v-btn v-else-if="isPending(o)" x-small text :loading="busy === o.orderId + ':status'" @click="refreshStatus(o)" title="Проверить статус сейчас">
                            <v-icon x-small left>mdi-refresh</v-icon>обновить
                        </v-btn>
                    </td>
                </tr>
                </tbody>
            </template>
        </v-simple-table>
                <div v-if="polling" class="caption grey--text mt-2">Статус обновляется раз в 30 с, пока заказ не готов</div>
    </div>
</template>

<script>
import moment from "moment";
import FileSaver from "file-saver";

const POLL_MS = 30000;
const STATUS = {
    CREATED: ['создан', 'grey'],
    PENDING: ['в работе', 'orange'],
    PARTIAL: ['частично', 'orange'],
    READY: ['готов', 'green'],
    REJECTED: ['отказ', 'red'],
};

/**
 * Заказы КМ по GTIN: справка «непокрыто» по партиям, заказ в СУЗ, статус (поллинг 30 с),
 * скачивание PDF-этикеток и CSV кодов. Всё через /api/chz/* и /api/good/{id}/uncovered.
 * Панель, а не диалог: живёт внутри диалога карточки Нацкаталога (NkCardDialog),
 * active — диалог открыт (грузим и опрашиваем), закрыт — гасим поллинг.
 */
export default {
    name: "GtinOrdersPanel",
    props: {
        active: {type: Boolean, required: true},
        gtin: {type: String, required: true},
        goodscode: {type: [Number, String], required: true},
    },
    filters: {
        date: (v) => v ? moment(v).format('DD.MM.YYYY') : '',
        datetime: (v) => v ? moment(v).format('DD.MM.YYYY HH:mm') : '',
    },
    data() {
        return {
            uncovered: null,
            orders: [],
            quantity: null,
            requestId: null,
            loading: false,
            ordering: false,
            busy: null,
            timer: null,
        }
    },
    computed: {
        canOrder() {
            return this.$store.getters['AUTH/HAS_PERMISSION']('good.update');
        },
        polling() {
            return !!this.timer;
        },
    },
    watch: {
        active: {
            immediate: true,
            handler(open) {
                open ? this.open() : this.close();
            },
        },
    },
    beforeDestroy() {
        this.close();
    },
    methods: {
        open() {
            this.uncovered = null;
            this.orders = [];
            this.quantity = null;
            this.newRequestId();
            this.loadUncovered();
            this.loadOrders();
        },
        close() {
            clearInterval(this.timer);
            this.timer = null;
        },
        // Идемпотентность заказа: один uuid на попытку, повтор после обрыва уходит с тем же id.
        newRequestId() {
            this.requestId = (window.crypto && crypto.randomUUID)
                ? crypto.randomUUID()
                : Date.now().toString(36) + '-' + Math.random().toString(36).slice(2);
        },
        loadUncovered() {
            axios.get('/api/good/' + this.goodscode + '/uncovered')
                .then(({data}) => {
                    this.uncovered = data;
                    // Дефолт = число партий: один код на партию. 0 партий — поле пустое.
                    if (this.quantity === null && data.parcels.length) this.quantity = data.parcels.length;
                })
                .catch((e) => this.error(e));
        },
        loadOrders() {
            this.loading = true;
            axios.get('/api/chz/gtin/' + this.gtin + '/orders')
                .then(({data}) => {
                    this.orders = data;
                    this.schedulePoll();
                })
                .catch((e) => this.error(e))
                .then(() => this.loading = false);
        },
        isPending(o) {
            return !o.fetched && o.status !== 'REJECTED';
        },
        // Поллинг только пока есть незавершённые заказы и диалог открыт.
        schedulePoll() {
            clearInterval(this.timer);
            this.timer = null;
            if (!this.active || !this.orders.some(this.isPending)) return;
            this.timer = setInterval(() => {
                this.orders.filter(this.isPending).forEach((o) => this.refreshStatus(o, true));
            }, POLL_MS);
        },
        refreshStatus(o, silent = false) {
            if (!silent) this.busy = o.orderId + ':status';
            return axios.get('/api/chz/order/' + o.orderId + '/status')
                .then(({data}) => {
                    const g = (data.gtins || []).find((x) => x.gtin === this.gtin) || {};
                    Object.assign(o, {status: data.status}, g);
                    if (!this.orders.some(this.isPending)) this.schedulePoll();
                })
                .catch((e) => silent || this.error(e))
                .then(() => { if (!silent) this.busy = null; });
        },
        order() {
            this.ordering = true;
            axios.post('/api/chz/gtin/' + this.gtin + '/orders', {quantity: this.quantity},
                {headers: {'X-Request-Id': this.requestId}})
                .then(({data}) => {
                    this.$store.commit('SNACKBAR/SUCCESS',
                        data.duplicate ? 'Такой заказ уже создан: ' + data.orderId : 'Заказ создан: ' + data.orderId, {root: true});
                    this.newRequestId();
                    this.loadOrders();
                })
                .catch((e) => this.error(e))
                .then(() => this.ordering = false);
        },
        // PDF отдаётся пачками — качаем все файлы списка подряд.
        downloadPdf(o) {
            this.busy = o.orderId + ':pdf';
            axios.get('/api/chz/order/' + o.orderId + '/pdf')
                .then(async ({data}) => {
                    for (const f of data.files || []) {
                        const res = await axios.get('/api/chz/order/' + o.orderId + '/pdf/' + f.n, {responseType: 'blob'});
                        FileSaver.saveAs(res.data, f.fileName);
                    }
                })
                .catch((e) => this.error(e))
                .then(() => this.busy = null);
        },
        downloadCsv(o) {
            this.busy = o.orderId + ':csv';
            axios.get('/api/chz/order/' + o.orderId + '/codes.csv', {responseType: 'blob'})
                .then((res) => FileSaver.saveAs(res.data, 'codes_' + o.orderId.slice(0, 8) + '_' + this.gtin + '.csv'))
                .catch((e) => this.error(e))
                .then(() => this.busy = null);
        },
        statusText(s) { return (STATUS[s] || [s])[0]; },
        statusColor(s) { return (STATUS[s] || [s, 'grey'])[1]; },
        parcelsWord(n) {
            const m10 = n % 10, m100 = n % 100;
            if (m10 === 1 && m100 !== 11) return 'партии';
            return 'партиях';
        },
        async error(e) {
            let response = e.response ? e.response.data : {};
            // Ошибка при responseType blob приходит блобом — читаем текст.
            if (response instanceof Blob) {
                try { response = JSON.parse(await response.text()); } catch (_) { response = {}; }
            }
            const message = response.errors
                ? Object.values(response.errors).flat().join(' ')
                : (response.message || 'Ошибка');
            this.$store.commit('SNACKBAR/ERROR', message, {root: true});
        },
    }
}
</script>
