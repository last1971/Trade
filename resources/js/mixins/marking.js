// Единый дом UI-логики ТНВЭД/ОКПД2 для всех экранов классификации
// (карточка, массовый разбор, страница-ревью). Данные — из стора MARKING.
// Компоненты подключают миксин и не держат своих копий этих computed/методов.
export default {
    computed: {
        // Список кодов ТНВЭД для combobox (маркируемые из справочника).
        tnvedItems() {
            return this.$store.getters['MARKING/DICT'].map(t => ({text: t.c + ' — ' + t.n, value: t.c}));
        },
    },
    methods: {
        // Варианты ОКПД2 для кода в формате combobox/select.
        okpd2Items(code) {
            return this.$store.getters['MARKING/OKPD2_OPTIONS'](code)
                .map(o => ({text: o.c + ' — ' + o.n, value: o.c}));
        },
        // v-combobox отдаёт объект при выборе из списка и строку при ручном вводе.
        normCode(v) {
            return v && typeof v === 'object' ? v.value : (v || '');
        },
        // Подлежит ли код маркировке (единый источник — стор).
        isMarkRequired(code) {
            return this.$store.getters['MARKING/IS_MARK_REQUIRED'](code);
        },
        // Один вариант ОКПД2 → он, иначе пусто (авто-подстановка при смене кода).
        defaultOkpd2(code) {
            const opts = this.okpd2Items(code);
            return opts.length === 1 ? opts[0].value : '';
        },
        // Служебный глиф-стрелка из справочника (🠺) → читаемый разделитель.
        cleanGlyph(s) {
            return (s || '').replace(/\s*[\u{1F800}-\u{1F8FF}]\s*/gu, ' / ');
        },
        // Запросить расшифровку кода (кэш в сторе) — вызывать при смене кода.
        // Возвращает промис с данными кода (или null) — чтобы обновить UI по месту.
        resolveTnved(code) {
            if (/^\d+$/.test(code)) {
                return this.$store.dispatch('MARKING/RESOLVE', code);
            }
            return Promise.resolve(null);
        },
        // Текст подстрочника «что это» по коду (из кэша резолва, реактивно).
        tnvedHint(code) {
            if (!code) {
                return '';
            }
            const r = this.$store.getters['MARKING/RESOLVED'](code);
            if (!r) {
                return '';
            }
            return r.found ? this.cleanGlyph(r.name) : 'код не найден в справочнике';
        },
    },
}
