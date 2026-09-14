<template>
    <v-card flat>
        <v-card-title class="subtitle-1 py-2">
            Код маркировки
            <v-spacer/>
            <!-- Со сканером в руках коды смотрят подряд: поле здесь же,
                 чтобы за следующим не возвращаться в список. -->
            <mark-code-scan class="scan-field mr-2"/>
            <v-btn small icon :loading="loading" title="Обновить" @click="load">
                <v-icon>mdi-refresh</v-icon>
            </v-btn>
        </v-card-title>
        <v-divider/>
        <v-card-text v-if="!code" class="grey--text">Загружается…</v-card-text>
        <v-row v-else class="ma-0">
            <!-- Наши данные и данные ЧЗ намеренно рядом, а не слиты: они расходятся
                 штатно. У нас «выведен» значит «продан», у ЧЗ — «выбыл из оборота». -->
            <v-col cols="12" md="6">
                <v-card outlined>
                    <v-card-title class="subtitle-2">В нашей базе</v-card-title>
                    <v-simple-table dense>
                        <tbody>
                        <tr>
                            <td class="label">КИ</td>
                            <td class="mono">{{ code.KI }}</td>
                        </tr>
                        <tr>
                            <td class="label">Товар</td>
                            <td>
                                <router-link v-if="code.GOODSCODE"
                                             :to="{ name: 'good', params: { id: code.GOODSCODE } }"
                                >{{ goodName || code.GOODSCODE }}</router-link>
                            </td>
                        </tr>
                        <tr>
                            <td class="label">GTIN</td>
                            <td>{{ code.GTIN }}</td>
                        </tr>
                        <tr>
                            <td class="label">Количество на коде</td>
                            <td>{{ code.QUANTITY || 1 }}</td>
                        </tr>
                        <tr>
                            <td class="label">Статус</td>
                            <td>{{ statusText(code.STATUS) }}</td>
                        </tr>
                        <tr>
                            <td class="label">Передан</td>
                            <td>{{ transferText(code.TRANSFER_TYPE) }}</td>
                        </tr>
                        <tr v-if="code.RETIRE_REASON">
                            <td class="label">Причина вывода</td>
                            <td>{{ retireText(code.RETIRE_REASON) }}</td>
                        </tr>
                        <tr>
                            <td class="label">Документ</td>
                            <td>
                                <router-link v-if="invoiceId" :to="{ name: 'invoice', params: { id: invoiceId } }">
                                    счёт {{ invoiceNumber }}
                                </router-link>
                                <router-link v-else-if="transferOutId"
                                             :to="{ name: 'transfer-out', params: { id: transferOutId } }"
                                >УПД {{ transferOutNumber }}</router-link>
                                <span v-else class="grey--text">нет</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Передан в ЧЗ</td>
                            <td>
                                <template v-if="code.CHZ_SENT_AT">
                                    {{ code.CHZ_SENT_AT | datetime }}
                                    <!-- Код закрыт без вывода (ушёл другому участнику) —
                                         причина лежит там же, где у снятых, но без даты снятия. -->
                                    <span v-if="!code.CHZ_SKIP_AT && code.CHZ_SKIP_TEXT" class="grey--text">
                                        — {{ code.CHZ_SKIP_TEXT }}
                                    </span>
                                </template>
                                <span v-else class="grey--text">нет</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Снят с отправки</td>
                            <td>
                                <template v-if="code.CHZ_SKIP_AT">
                                    {{ code.CHZ_SKIP_AT | datetime }} — {{ code.CHZ_SKIP_TEXT }}
                                    <v-btn x-small text :loading="busy" @click="unskip">Вернуть в очередь</v-btn>
                                </template>
                                <span v-else class="grey--text">нет</span>
                            </td>
                        </tr>
                        </tbody>
                    </v-simple-table>
                </v-card>
            </v-col>

            <v-col cols="12" md="6">
                <v-card outlined>
                    <v-card-title class="subtitle-2">
                        В Честном знаке
                        <v-spacer/>
                        <v-progress-circular v-if="chzLoading" indeterminate size="16" width="2"/>
                    </v-card-title>
                    <v-card-text v-if="chzError" class="red--text">{{ chzError }}</v-card-text>
                    <v-simple-table v-else-if="chz" dense>
                        <tbody>
                        <tr>
                            <td class="label">Статус</td>
                            <td>{{ chz.status }}</td>
                        </tr>
                        <tr>
                            <td class="label">Владелец</td>
                            <td :class="{'red--text': alien}">
                                {{ chzRaw.ownerName || '—' }} <span v-if="chz.ownerInn">({{ chz.ownerInn }})</span>
                                <span v-if="alien"> — код не наш</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Товар</td>
                            <td>{{ chz.productName }}</td>
                        </tr>
                        <tr>
                            <td class="label">Количество</td>
                            <td>{{ chz.quantity }}</td>
                        </tr>
                        <tr>
                            <td class="label">Нанесён</td>
                            <td>{{ chz.appliedAt | datetime }}</td>
                        </tr>
                        <tr>
                            <td class="label">Введён в оборот</td>
                            <td>{{ chz.introducedAt | datetime }}</td>
                        </tr>
                        <tr v-if="chzRaw.splitSource">
                            <td class="label">Отделён от</td>
                            <td class="mono">{{ chzRaw.splitSource }}</td>
                        </tr>
                        <tr>
                            <td class="label">Производитель</td>
                            <td>{{ chzRaw.producerName || chzRaw.producerInn || '—' }}</td>
                        </tr>
                        </tbody>
                    </v-simple-table>
                    <v-card-text v-else class="grey--text">Спрашиваем Честный знак…</v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-card>
</template>

<script>
import moment from "moment";
import markCodeTableMixin from "../../mixins/markCodeTableMixin";
import MarkCodeScan from "./MarkCodeScan";

/**
 * Карточка одного кода маркировки: наши данные и живой ответ ГИС МТ рядом.
 * Нужна там, где код повёл себя не так — отбился при выводе, числится за чужим
 * участником, «выведен» у нас и «в обороте» у ЧЗ.
 */
export default {
    name: "MarkCode",
    components: {MarkCodeScan},
    mixins: [markCodeTableMixin],
    filters: {
        datetime: (v) => v ? moment(v).format('DD.MM.YYYY HH:mm') : '',
    },
    data() {
        return {
            code: null,
            chz: null,
            chzRaw: {},
            chzError: '',
            // Владелец в ГИС МТ не наш — считает сервер, у него ИНН организации.
            alien: false,
            loading: false,
            chzLoading: false,
            busy: false,
        };
    },
    computed: {
        goodName() {
            return this.code && this.code.good && this.code.good.name ? this.code.good.name.NAME : null;
        },
        invoiceId() {
            const line = this.code && this.code.invoiceLine;
            return line && line.invoice ? line.invoice.SCODE : null;
        },
        invoiceNumber() {
            const line = this.code && this.code.invoiceLine;
            return line && line.invoice ? line.invoice.NS : null;
        },
        transferOutId() {
            const line = this.code && this.code.transferOutLine;
            return line && line.transferOut ? line.transferOut.SFCODE : null;
        },
        transferOutNumber() {
            const line = this.code && this.code.transferOutLine;
            return line && line.transferOut ? line.transferOut.NSF : null;
        },
    },
    created() {
        this.crumb();
        this.load();
    },
    watch: {
        // Переход с карточки на карточку (из поля скана, из пачки, кнопкой
        // «назад») меняет только параметр маршрута — created второй раз не
        // случится, и без этого на экране остаётся предыдущий код.
        '$route.params.id'() {
            this.code = null;
            this.chz = null;
            this.chzRaw = {};
            this.chzError = '';
            this.alien = false;
            this.crumb();
            this.load();
        },
    },
    methods: {
        /**
         * Своя крошка в цепочке. Ставится сразу, до ответа сервера: иначе на
         * карточке висит цепочка предыдущей страницы (пришёл из «Отправки в ЧЗ» —
         * видишь «Отправка в ЧЗ»), а при переходе карточка→карточка не менялась бы
         * вовсе. PUT заменяет крошку этого же маршрута, поэтому откуда пришли —
         * сохраняется, а КИ подставляется, когда данные приедут.
         */
        crumb(text) {
            if (!this.$store.getters['BREADCRUMBS/ALL'].length && this.$route.meta.breadcrumbs) {
                this.$store.commit('BREADCRUMBS/SET', [...this.$route.meta.breadcrumbs]);
            }
            this.$store.commit('BREADCRUMBS/PUT', {
                text: text || 'Код маркировки',
                to: {name: 'mark-code', params: {id: this.$route.params.id}},
                exact: true,
            });
        },
        retireText(reason) {
            return {
                1: 'продажа', 2: 'списание', 3: 'передача (B2B/FBO)',
                4: 'брак', 5: 'собственные нужды', 6: 'перемаркировка',
            }[reason] || reason;
        },
        load() {
            this.loading = true;
            const params = {
                with: ['good.name', 'invoiceLine.invoice', 'transferOutLine.transferOut'],
            };
            axios.get('/api/mark-code/' + this.$route.params.id, {params})
                .then(({data}) => {
                    this.code = data;
                    this.crumb(String(data.KI));
                    this.askChz();
                })
                .catch(this.error)
                .then(() => this.loading = false);
        },
        // Живой запрос в ГИС МТ: он идёт через chz-сервис и заметно дольше нашей базы,
        // поэтому карточка рисуется сразу, а этот блок догружается.
        askChz() {
            this.chzLoading = true;
            this.chzError = '';
            axios.get('/api/mark-code/' + this.$route.params.id + '/chz-info')
                .then(({data}) => {
                    if (!data.ok) {
                        this.chzError = data.error;
                        return;
                    }
                    this.chz = data.code;
                    this.chzRaw = (data.code && data.code.raw) || {};
                    this.alien = !!data.alien;
                })
                .catch((e) => this.chzError = (e.response && e.response.data.message) || 'Честный знак не ответил')
                .then(() => this.chzLoading = false);
        },
        unskip() {
            this.busy = true;
            axios.post('/api/mark-code/' + this.$route.params.id + '/unskip')
                .then(() => {
                    this.$store.commit('SNACKBAR/SUCCESS', 'Код вернулся в очередь', {root: true});
                    this.load();
                })
                .catch(this.error)
                .then(() => this.busy = false);
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
    word-break: break-all;
}

.label {
    width: 200px;
    color: rgba(0, 0, 0, 0.6);
}

/* Поле скана в заголовке карточки: сам код длинный, но растягивать поле
   на всю ширину незачем — его только заполняют сканером. */
.scan-field {
    max-width: 520px;
}
</style>
