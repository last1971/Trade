<template>
    <v-dialog v-model="show" max-width="760" scrollable>
        <v-card>
            <v-card-title class="subtitle-1 py-2">
                Создать карточку в Национальном каталоге
                <v-spacer/>
                <v-btn icon @click="show = false"><v-icon>mdi-close</v-icon></v-btn>
            </v-card-title>
            <v-divider/>
            <v-card-text class="pt-3">
                <div class="caption grey--text mb-3">
                    Карточка ИМ: GTIN каталог присвоит сам. ТН ВЭД <b>{{ row.TNVED }}</b>, ОКПД2 <b>{{ row.OKPD2 }}</b> —
                    со строки товара, менять здесь нельзя.
                </div>

                <v-select
                    v-model="catId"
                    :items="categories"
                    item-text="cat_name"
                    item-value="cat_id"
                    label="Категория каталога"
                    dense outlined
                    :loading="loadingCategories"
                    :disabled="categories.length <= 1"
                    :hint="categories.length > 1 ? 'По этому ТН ВЭД категорий несколько — выберите' : ''"
                    persistent-hint
                    class="mb-3"
                />

                <v-select
                    v-model="vid"
                    :items="vids"
                    label="Вид товара"
                    dense outlined
                    :loading="loadingAttributes"
                    :disabled="!catId"
                    class="mb-3"
                />
                <v-text-field
                    v-if="vid === VID_ABSENT"
                    v-model="vidOther"
                    label="Вид товара словами (обязательно)"
                    dense outlined
                    hint="В справочнике нет подходящего вида — напишите, что это: «Разъём USB», «Штекер питания»"
                    persistent-hint
                    class="mb-3"
                />

                <v-text-field
                    v-model="name"
                    label="Наименование карточки"
                    dense outlined
                    counter="200"
                    hint="Родовое слово + модель, например «Блок питания RS-100-5»"
                    persistent-hint
                    class="mb-3"
                    @input="nameTouched = true"
                />

                <v-autocomplete
                    v-model="brand"
                    :items="brands"
                    :search-input.sync="brandQuery"
                    :loading="loadingBrands"
                    label="Товарный знак (из справочника каталога)"
                    dense outlined clearable
                    no-filter
                    hint="Пусто — в карточке будет «ОТСУТСТВУЕТ»"
                    persistent-hint
                    class="mb-3"
                />

                <v-checkbox v-model="moderation" dense hide-details label="Сразу отправить на модерацию"/>
            </v-card-text>
            <v-divider/>
            <v-card-actions>
                <v-spacer/>
                <v-btn text @click="show = false">Отмена</v-btn>
                <v-btn color="primary" :loading="creating" :disabled="!canCreate" @click="create">Создать</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
const VID_ABSENT = 'НЕТ В СПРАВОЧНИКЕ';
const BRAND_DEBOUNCE_MS = 400;

/**
 * Форма создания карточки ИМ по строке GOODS_CLASSIF без GTIN. Данных для карточки в базе
 * нет — категория и вид берутся из справочников каталога, наименование предзаполняется
 * «вид + партномер», бренд — точным совпадением GOODS.PRODUCER со справочником.
 * Идемпотентность — на бэке (детерминированный X-Request-Id): второй клик карточку не задвоит.
 */
export default {
    name: "NkCardCreate",
    props: {
        value: {type: Boolean, required: true},
        row: {type: Object, required: true},
        good: {type: Object, default: null},
    },
    data() {
        return {
            VID_ABSENT,
            categories: [],
            catId: null,
            vids: [],
            vid: null,
            vidOther: '',
            name: '',
            nameTouched: false,
            brands: [],
            brand: null,
            brandQuery: null,
            brandTimer: null,
            moderation: true,
            loadingCategories: false,
            loadingAttributes: false,
            loadingBrands: false,
            creating: false,
        }
    },
    computed: {
        show: {
            get() { return this.value; },
            set(v) { this.$emit('input', v); },
        },
        partNumber() {
            return this.good && this.good.name && this.good.name.NAME ? this.good.name.NAME.trim() : '';
        },
        producer() {
            return this.good && this.good.PRODUCER ? this.good.PRODUCER.trim() : '';
        },
        canCreate() {
            return !!(this.catId && this.vid && this.name.trim() && (this.vid !== VID_ABSENT || this.vidOther.trim()))
                && !this.creating;
        },
    },
    watch: {
        value(open) {
            if (open) this.open();
        },
        catId(id) {
            this.vid = null;
            this.vids = [];
            if (id) this.loadAttributes(id);
        },
        vid(v) {
            // Наименование = «вид + партномер», пока человек его не правил сам.
            if (v && v !== VID_ABSENT && !this.nameTouched) this.name = this.suggestName(v);
        },
        brandQuery(q) {
            clearTimeout(this.brandTimer);
            if (!q || q === this.brand) return;
            this.brandTimer = setTimeout(() => this.loadBrands(q), BRAND_DEBOUNCE_MS);
        },
    },
    methods: {
        open() {
            Object.assign(this.$data, {
                categories: [], catId: null, vids: [], vid: null, vidOther: '',
                name: '', nameTouched: false, brands: [], brand: null, brandQuery: null, moderation: true,
            });
            this.loadCategories();
            if (this.producer) this.loadBrands(this.producer, true);
        },
        loadCategories() {
            this.loadingCategories = true;
            axios.get('/api/nk/categories', {params: {tnved: this.row.TNVED}})
                .then(({data}) => {
                    this.categories = data;
                    if (data.length === 1) this.catId = data[0].cat_id;
                })
                .catch((e) => this.error(e))
                .then(() => this.loadingCategories = false);
        },
        // Вид товара — строго из attr_preset атрибута 12 категории.
        loadAttributes(catId) {
            this.loadingAttributes = true;
            axios.get('/api/nk/attributes', {params: {catId}})
                .then(({data}) => {
                    const attr = data.find((a) => String(a.attr_id) === '12');
                    this.vids = ((attr && attr.attr_preset) || []).map((p) => (p && typeof p === 'object') ? p.value : p);
                })
                .catch((e) => this.error(e))
                .then(() => this.loadingAttributes = false);
        },
        // Справочник брендов; exact — предзаполнение по PRODUCER: берём только точное совпадение.
        loadBrands(q, exact = false) {
            this.loadingBrands = true;
            axios.get('/api/nk/brands', {params: {q}})
                .then(({data}) => {
                    const names = data.map((b) => b.brand_name);
                    if (exact) {
                        const hit = names.find((n) => n.trim().toUpperCase() === q.toUpperCase());
                        if (hit) {
                            this.brands = [hit];
                            this.brand = hit;
                        }
                        return;
                    }
                    this.brands = this.brand && !names.includes(this.brand) ? [this.brand, ...names] : names;
                })
                .catch(() => {})
                .then(() => this.loadingBrands = false);
        },
        // «БЛОК ПИТАНИЯ» → «Блок питания RS-100-5»
        suggestName(vid) {
            const word = vid.toLowerCase();
            return (word.charAt(0).toUpperCase() + word.slice(1) + ' ' + this.partNumber).trim();
        },
        create() {
            this.creating = true;
            axios.post('/api/nk/classif/' + this.row.ID + '/card', {
                name: this.name.trim(),
                catId: this.catId,
                vid: this.vid,
                vidOther: this.vid === VID_ABSENT ? this.vidOther.trim() : null,
                brand: this.brand || null,
                moderation: this.moderation,
            })
                .then(({data}) => {
                    this.$emit('created', data);
                    this.$store.commit('SNACKBAR/SUCCESS',
                        data.GTIN ? 'Карточка создана, GTIN ' + data.GTIN : 'Карточка отправлена в каталог, ждём разбора', {root: true});
                    this.show = false;
                })
                .catch((e) => this.error(e))
                .then(() => this.creating = false);
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
