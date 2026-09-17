<template>
    <v-card outlined class="my-4">
        <v-card-title class="subtitle-2 py-2">Национальный каталог (GTIN)</v-card-title>
        <v-divider/>
        <v-card-text class="py-2 d-flex align-center flex-wrap" style="gap: 12px">
            <span>
                Маркировка:
                <v-chip small :color="verdictColor" outlined>{{ verdictText }}</v-chip>
            </span>
            <template v-if="!notEditable">
                <verdict-picker v-model="verdict" field-width="420px"/>
                <v-text-field
                    v-model="verdictPrim"
                    label="Примечание (почему)"
                    dense hide-details clearable
                    style="max-width: 420px"
                />
                <v-btn small outlined color="blue" :loading="suggesting" :disabled="!canSuggest" @click="suggest" title="Подобрать код ТН ВЭД по данным товара">
                    <v-icon small left>mdi-magnify</v-icon>
                    Подобрать код
                </v-btn>
                <v-btn small color="primary" :loading="classifying" @click="classify(isMarkRequired(verdict.tnved) ? 1 : 0)">
                    Классифицировать
                </v-btn>
                <span v-if="suggestion" class="caption" style="flex-basis: 100%">
                    Подбор: <b>{{ suggestion.tnved }}</b> · {{ suggestionName }}
                    · {{ suggestion.mark_required ? 'подлежит' : 'не подлежит' }}
                    · уверенность {{ suggestion.confidence }}%{{ suggestion.confidence < 80 ? ' — низкая, проверьте' : '' }}
                </span>
            </template>
        </v-card-text>
        <v-divider/>
        <v-simple-table dense>
            <template v-slot:default>
                <thead>
                <tr>
                    <th>GTIN</th>
                    <th>ТНВЭД</th>
                    <th>ОКПД2</th>
                    <th>ИНН поставщика</th>
                    <th>Осн.</th>
                    <th>Марк.</th>
                    <th>Кодов ЧЗ</th>
                    <th>Примечание</th>
                    <th style="width: 110px"></th>
                </tr>
                </thead>
                <tbody>
                <tr v-if="!rows.length && !adding">
                    <td colspan="9" class="text-center">Отсутствуют данные</td>
                </tr>
                <tr v-for="row in rows" :key="row.ID">
                    <template v-if="editId === row.ID">
                        <td>
                            <v-text-field v-model="form.GTIN" dense hide-details/>
                        </td>
                        <td>
                            <v-text-field v-model="form.TNVED" dense hide-details/>
                        </td>
                        <td>
                            <v-text-field v-model="form.OKPD2" dense hide-details/>
                        </td>
                        <td>
                            <v-text-field v-model="form.SUPPLIER_INN" dense hide-details/>
                        </td>
                        <td>{{ row.IS_PRIMARY ? 'да' : '' }}</td>
                        <td>{{ row.MARK_REQUIRED ? 'да' : 'нет' }}</td>
                        <td>{{ row.mark_codes_count }}</td>
                        <td>
                            <v-text-field v-model="form.PRIM" dense hide-details placeholder="Примечание"/>
                        </td>
                        <td>
                            <v-btn icon small @click="saveEdit(row)" title="Сохранить">
                                <v-icon small color="green">mdi-content-save</v-icon>
                            </v-btn>
                            <v-btn icon small @click="editId = null" title="Отмена">
                                <v-icon small color="red">mdi-close</v-icon>
                            </v-btn>
                        </td>
                    </template>
                    <template v-else>
                        <td>
                            <template v-if="row.GTIN">
                                <a href="#" @click.prevent="openCard(row)" title="Карточка Нацкаталога и заказы КМ по этому GTIN">{{ row.GTIN }}</a>
                                <v-chip v-if="row.NK_STATE" x-small outlined :color="nkStateColor(row.NK_STATE)" class="ml-1"
                                        :title="row.NK_STATE_TEXT || ''">{{ nkStateText(row.NK_STATE) }}</v-chip>
                            </template>
                            <template v-else-if="isOwn(row) && !notEditable">
                                <v-chip v-if="row.NK_STATE === 'creating'" x-small outlined color="orange">создаётся…</v-chip>
                                <template v-else>
                                    <v-btn x-small outlined color="green" @click="openCreate(row)" title="Завести карточку ИМ в Нацкаталоге: GTIN присвоит каталог">
                                        <v-icon x-small left>mdi-plus</v-icon>Создать карточку
                                    </v-btn>
                                    <div v-if="row.NK_STATE === 'rejected'" class="red--text caption">{{ row.NK_STATE_TEXT }}</div>
                                </template>
                            </template>
                            <span v-else>—</span>
                        </td>
                        <td>{{ row.TNVED }}</td>
                        <td>{{ row.OKPD2 }}</td>
                        <td>{{ row.SUPPLIER_INN }}</td>
                        <td>{{ row.IS_PRIMARY ? 'да' : '' }}</td>
                        <td>{{ row.MARK_REQUIRED ? 'да' : 'нет' }}</td>
                        <td>{{ row.mark_codes_count }}</td>
                        <td>{{ row.PRIM }}</td>
                        <td>
                            <v-btn icon small
                                   :disabled="notEditable || row.mark_codes_count > 0"
                                   @click="startEdit(row)"
                                   :title="row.mark_codes_count > 0 ? 'Есть коды Честного знака — менять нельзя' : 'Редактировать'"
                            >
                                <v-icon small>mdi-pencil</v-icon>
                            </v-btn>
                            <v-btn icon small
                                   :disabled="notEditable || row.mark_codes_count > 0"
                                   @click="remove(row)"
                                   :title="row.mark_codes_count > 0 ? 'Есть коды Честного знака — удалять нельзя' : 'Удалить'"
                            >
                                <v-icon small color="red">mdi-delete</v-icon>
                            </v-btn>
                        </td>
                    </template>
                </tr>
                <tr v-if="adding">
                    <td>
                        <v-text-field v-model="form.GTIN" dense hide-details placeholder="GTIN"/>
                    </td>
                    <td>
                        <v-text-field v-model="form.TNVED" dense hide-details placeholder="ТНВЭД"/>
                    </td>
                    <td>
                        <v-text-field v-model="form.OKPD2" dense hide-details placeholder="ОКПД2"/>
                    </td>
                    <td>
                        <v-text-field v-model="form.SUPPLIER_INN" dense hide-details placeholder="ИНН"/>
                    </td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td>
                        <v-text-field v-model="form.PRIM" dense hide-details placeholder="Примечание"/>
                    </td>
                    <td>
                        <v-btn icon small @click="saveNew" title="Сохранить">
                            <v-icon small color="green">mdi-content-save</v-icon>
                        </v-btn>
                        <v-btn icon small @click="adding = false" title="Отмена">
                            <v-icon small color="red">mdi-close</v-icon>
                        </v-btn>
                    </td>
                </tr>
                </tbody>
            </template>
        </v-simple-table>
        <nk-card-dialog v-if="cardRow" v-model="cardOpen" :row="cardRow" :goodscode="value" @updated="replaceRow"/>
        <nk-card-create v-if="createRow" v-model="createOpen" :row="createRow" :good="good" @created="onCreated"/>
        <v-card-actions v-if="!adding && !notEditable">
            <v-btn small text color="green" @click="startAdd">
                <v-icon small left>mdi-plus</v-icon>
                Добавить GTIN
            </v-btn>
        </v-card-actions>
    </v-card>
</template>

<script>
import marking from "../../mixins/marking";
import VerdictPicker from "./VerdictPicker";
import NkCardDialog from "./NkCardDialog";
import NkCardCreate from "./NkCardCreate";

export default {
    name: "GoodGtins",
    components: {VerdictPicker, NkCardDialog, NkCardCreate},
    mixins: [marking],
    props: {
        value: {type: [Number, String], required: true},
        good: {type: Object, default: null},
    },
    data() {
        return {
            rows: [],
            adding: false,
            editId: null,
            classifying: false,
            suggesting: false,
            suggestion: null,
            verdict: {tnved: '', okpd2: ''},
            verdictPrim: '',
            form: {GTIN: '', TNVED: '', OKPD2: '', SUPPLIER_INN: '', PRIM: ''},
            cardOpen: false,
            cardRow: null,
            createOpen: false,
            createRow: null,
            stateTimer: null,
        }
    },
    computed: {
        notEditable() {
            return !this.$store.getters['AUTH/HAS_PERMISSION']('good.update');
        },
        // Есть чем подбирать — нужно хотя бы название товара.
        canSuggest() {
            return !!(this.good && this.good.name && this.good.name.NAME) && !this.classifying;
        },
        // Наименование подбора без служебного глифа-стрелки (mixin).
        suggestionName() {
            return this.suggestion ? this.cleanGlyph(this.suggestion.tnved_name) : '';
        },
        // Вердикт по товару: «подлежит?» = есть строка с MARK_REQUIRED=1.
        verdictText() {
            if (!this.rows.length) return 'не проверяли';
            return this.rows.some(row => row.MARK_REQUIRED) ? 'подлежит' : 'не подлежит';
        },
        verdictColor() {
            if (!this.rows.length) return 'grey';
            return this.rows.some(row => row.MARK_REQUIRED) ? 'orange' : 'green';
        },
    },
    watch: {
        value: {
            immediate: true,
            handler() {
                this.load();
            }
        }
    },
    created() {
        // Справочник маркировки — общий стор, тянется один раз за сессию.
        this.$store.dispatch('MARKING/FETCH');
    },
    beforeDestroy() {
        clearInterval(this.stateTimer);
    },
    methods: {
        load() {
            // Инстанс модалки переиспользуется — гасим подбор от прошлой карточки.
            this.suggestion = null;
            if (!this.value) {
                this.rows = [];
                return;
            }
            axios.get('/api/good/' + this.value + '/gtins')
                .then((response) => {
                    this.rows = response.data;
                    this.fillVerdictTnved();
                    this.schedulePoll();
                })
                .catch(() => {
                    this.rows = [];
                });
        },
        // ТНВЭД/ОКПД2/примечание вердикта живут на основной (IS_PRIMARY=1) строке товара.
        fillVerdictTnved() {
            const primary = this.rows.find(row => row.IS_PRIMARY);
            this.verdict = {
                tnved: primary ? (primary.TNVED || '') : '',
                okpd2: primary ? (primary.OKPD2 || '') : '',
            };
            this.verdictPrim = primary ? (primary.PRIM || '') : '';
        },
        // Подбор через тот же движок, что пачка/команда (classifyOne на бэке):
        // заполняет код + ОКПД2, показывает подлежит/уверенность. В базу не пишет —
        // сохраняет пользователь кнопкой «Классифицировать».
        suggest() {
            if (!this.canSuggest) return;
            this.suggesting = true;
            this.suggestion = null;
            axios.post('/api/good/' + this.value + '/suggest')
                .then(({data}) => {
                    if (!data || data.status !== 'ok') {
                        this.$store.commit('SNACKBAR/ERROR',
                            'Не удалось подобрать: ' + ((data && data.reason) || 'нет результата'), {root: true});
                        return;
                    }
                    this.suggestion = data;
                    this.verdict = {tnved: data.tnved, okpd2: data.okpd2 || ''};
                })
                .catch((e) => this.error(e))
                .then(() => this.suggesting = false);
        },
        classify(markRequired) {
            this.classifying = true;
            axios.post('/api/good/' + this.value + '/classify', {
                MARK_REQUIRED: markRequired,
                TNVED: this.verdict.tnved || null,
                OKPD2: this.verdict.okpd2 || null,
                PRIM: this.verdictPrim || null,
            })
                .then((response) => {
                    this.rows = response.data;
                    this.fillVerdictTnved();
                    this.$emit('classified');
                })
                .catch((e) => this.error(e))
                .then(() => this.classifying = false);
        },
        // Своя строка — без ИНН поставщика; чужой GTIN в каталоге не ищем и карточку по нему не заводим.
        isOwn(row) {
            return !(row.SUPPLIER_INN && String(row.SUPPLIER_INN).trim());
        },
        openCard(row) {
            this.cardRow = row;
            this.$nextTick(() => this.cardOpen = true);
        },
        openCreate(row) {
            this.createRow = row;
            this.$nextTick(() => this.createOpen = true);
        },
        // Бэк вернул строку с обновлённым снимком NK_* — подменяем на месте, без перечитывания списка.
        replaceRow(fresh) {
            const i = this.rows.findIndex((r) => r.ID === fresh.ID);
            if (i >= 0) this.$set(this.rows, i, Object.assign({}, this.rows[i], fresh));
            this.schedulePoll();
        },
        onCreated(fresh) {
            this.replaceRow(fresh);
            this.$emit('classified');
        },
        // Пока есть строка «создаётся…» — раз в 30 с дочитываем результат.
        schedulePoll() {
            clearInterval(this.stateTimer);
            this.stateTimer = null;
            if (!this.rows.some((r) => r.NK_STATE === 'creating')) return;
            this.stateTimer = setInterval(() => {
                this.rows.filter((r) => r.NK_STATE === 'creating').forEach((r) => {
                    axios.get('/api/nk/classif/' + r.ID + '/state')
                        .then(({data}) => { if (data.NK_STATE !== 'creating') this.replaceRow(data); })
                        .catch(() => {});
                });
            }, 30000);
        },
        startAdd() {
            this.editId = null;
            this.form = {GTIN: '', TNVED: '', OKPD2: '', SUPPLIER_INN: '', PRIM: ''};
            this.adding = true;
        },
        startEdit(row) {
            this.adding = false;
            this.form = {
                GTIN: row.GTIN,
                TNVED: row.TNVED,
                OKPD2: row.OKPD2,
                SUPPLIER_INN: row.SUPPLIER_INN,
                PRIM: row.PRIM,
            };
            this.editId = row.ID;
        },
        saveNew() {
            axios.post('/api/good/' + this.value + '/gtins', this.form)
                .then(() => {
                    this.adding = false;
                    this.load();
                })
                .catch((e) => this.error(e));
        },
        saveEdit(row) {
            axios.put('/api/good-gtin/' + row.ID, this.form)
                .then(() => {
                    this.editId = null;
                    this.load();
                })
                .catch((e) => this.error(e));
        },
        remove(row) {
            if (!confirm('Удалить GTIN ' + row.GTIN + '?')) return;
            axios.delete('/api/good-gtin/' + row.ID)
                .then(() => this.load())
                .catch((e) => this.error(e));
        },
        error(e) {
            const response = e.response ? e.response.data : {};
            const message = response.errors
                ? Object.values(response.errors).flat().join(' ')
                : (response.message || 'Ошибка');
            this.$store.commit('SNACKBAR/ERROR', message, {root: true});
        },
    }
}
</script>

<style scoped>

</style>
