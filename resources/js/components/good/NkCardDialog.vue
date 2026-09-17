<template>
    <v-dialog v-model="show" max-width="1000" scrollable>
        <v-card>
            <v-card-title class="subtitle-1 py-2">
                Национальный каталог · GTIN {{ row.GTIN }}
                <v-spacer/>
                <v-btn icon @click="show = false"><v-icon>mdi-close</v-icon></v-btn>
            </v-card-title>
            <v-divider/>
            <v-card-text class="pt-3">
                <!-- Карточка -->
                <div v-if="loading" class="grey--text mb-4">Читаю каталог…</div>

                <v-alert v-else-if="kind === 'supplier'" type="info" dense outlined class="mb-4">
                    GTIN поставщика (ИНН {{ row.SUPPLIER_INN }}) — карточка чужая, наш каталог её не показывает.
                </v-alert>

                <v-alert v-else-if="notFound" type="warning" dense outlined class="mb-4">
                    В нашем каталоге карточки с этим GTIN нет.
                </v-alert>

                <div v-else-if="card" class="mb-4">
                    <div class="d-flex align-center flex-wrap mb-2" style="gap: 12px">
                        <v-chip small :color="nkStateColor(state)" outlined>{{ nkStateText(state) }}</v-chip>
                        <a v-if="card.url" :href="card.url" target="_blank" rel="noopener">открыть в каталоге</a>
                        <span v-if="foreignOrg" class="caption orange--text">
                            карточка организации с ИНН {{ card.producerInn }} — не этого узла
                        </span>
                        <v-spacer/>
                        <template v-if="state === 'notsigned' && canUpdate">
                            <v-checkbox v-model="publication" dense hide-details class="mt-0"
                                        label="показывать на сайте каталога"/>
                            <v-btn small color="primary" :loading="signing" @click="sign">Опубликовать</v-btn>
                        </template>
                    </div>
                    <div v-if="stateText" class="red--text caption mb-2">{{ stateText }}</div>
                    <v-simple-table dense>
                        <template v-slot:default>
                            <tbody>
                            <tr><td class="grey--text" style="width: 180px">Наименование</td><td>{{ card.name }}</td></tr>
                            <tr><td class="grey--text">Вид товара</td><td>{{ card.vid }}<span v-if="card.vidOther"> — {{ card.vidOther }}</span></td></tr>
                            <tr><td class="grey--text">Товарный знак</td><td>{{ card.brand }}</td></tr>
                            <tr><td class="grey--text">Категория</td><td>{{ card.category }}</td></tr>
                            <tr><td class="grey--text">ТН ВЭД / ОКПД2</td><td>{{ card.tnved }} / {{ card.okpd2 }}</td></tr>
                            <tr><td class="grey--text">good_id</td><td>{{ card.goodId }}</td></tr>
                            </tbody>
                        </template>
                    </v-simple-table>
                </div>

                <v-divider class="my-3"/>

                <!-- Выпуск КМ — остаётся в этом же блоке -->
                <gtin-orders-panel v-if="row.GTIN" :active="value" :gtin="row.GTIN" :goodscode="goodscode"/>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script>
import marking from "../../mixins/marking";
import GtinOrdersPanel from "./GtinOrdersPanel";

/**
 * Диалог по клику на GTIN: карточка Нацкаталога (чья, статус, публикация) + заказы КМ.
 * Чья — решает бэк по строке GOODS_CLASSIF (SUPPLIER_INN): чужую в каталоге не ищем.
 * Снимок NK_* строки бэк освежает при каждом открытии; родителю отдаём обновлённую строку.
 */
export default {
    name: "NkCardDialog",
    components: {GtinOrdersPanel},
    mixins: [marking],
    props: {
        value: {type: Boolean, required: true},
        row: {type: Object, required: true},
        goodscode: {type: [Number, String], required: true},
    },
    data() {
        return {
            loading: false,
            signing: false,
            kind: null,
            card: null,
            notFound: false,
            freshRow: null,
            publication: false,
        }
    },
    computed: {
        show: {
            get() { return this.value; },
            set(v) { this.$emit('input', v); },
        },
        canUpdate() {
            return this.$store.getters['AUTH/HAS_PERMISSION']('good.update');
        },
        state() {
            return (this.freshRow || this.row).NK_STATE;
        },
        stateText() {
            return (this.freshRow || this.row).NK_STATE_TEXT;
        },
        // Бэк сверил producer_inn карточки с ИНН узла — чужая своя же организация только помечается.
        foreignOrg() {
            return !!(this.card && this.card.ownOrg === false);
        },
    },
    watch: {
        value(open) {
            if (open) this.load();
        },
    },
    methods: {
        load() {
            this.loading = true;
            this.kind = null;
            this.card = null;
            this.notFound = false;
            this.freshRow = null;
            axios.get('/api/nk/classif/' + this.row.ID + '/card')
                .then(({data}) => {
                    this.kind = data.kind;
                    this.card = data.card;
                    this.notFound = !!data.notFound;
                    this.freshRow = data.row;
                    this.$emit('updated', data.row);
                })
                .catch((e) => this.error(e))
                .then(() => this.loading = false);
        },
        sign() {
            this.signing = true;
            axios.post('/api/nk/classif/' + this.row.ID + '/sign', {publication: this.publication})
                .then(({data}) => {
                    this.freshRow = data;
                    this.$emit('updated', data);
                    this.$store.commit('SNACKBAR/SUCCESS', 'Карточка подписана и опубликована', {root: true});
                })
                .catch((e) => this.error(e))
                .then(() => this.signing = false);
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
