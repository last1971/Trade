// Справочник маркируемых ТН ВЭД → ОКПД2. Единственный источник на фронте —
// ручка /api/marking/dict (бэк MarkingDictService). Тянется один раз за сессию
// и переиспользуется карточкой, массовым разбором и страницей-ревью.
export default {
    namespaced: true,
    state: {
        dict: [],
        loaded: false,
        // Кэш расшифровок кодов ТНВЭД: code → {found, name, tariff, mark_required, okpd2}.
        resolved: {},
    },
    getters: {
        // Весь справочник: [{c, n, o:[{c, n}]}].
        DICT: state => state.dict,
        // Варианты ОКПД2 для кода (пусто, если код не маркируется).
        OKPD2_OPTIONS: state => code => {
            const entry = state.dict.find(t => t.c === code);
            return entry ? entry.o : [];
        },
        // Подлежит ли код маркировке = есть в справочнике (та же логика, что на бэке).
        IS_MARK_REQUIRED: state => code => state.dict.some(t => t.c === code),
        // Расшифровка кода из кэша (null, если ещё не резолвили).
        RESOLVED: state => code => state.resolved[code] || null,
    },
    mutations: {
        SET(state, dict) {
            state.dict = dict;
            state.loaded = true;
        },
        SET_RESOLVED(state, {code, data}) {
            // Новый ключ через переприсвоение — иначе Vue 2 не увидит реактивность.
            state.resolved = {...state.resolved, [code]: data};
        },
    },
    actions: {
        // Загрузить справочник один раз; повторные вызовы — no-op.
        FETCH({state, commit}) {
            if (state.loaded) {
                return Promise.resolve(state.dict);
            }
            return axios.get('/api/marking/dict')
                .then(response => {
                    commit('SET', response.data);
                    return response.data;
                });
        },
        // Расшифровка кода «что это»: из кэша или разово с бэка (/api/tnved/{code}).
        RESOLVE({state, commit}, code) {
            if (state.resolved[code]) {
                return Promise.resolve(state.resolved[code]);
            }
            return axios.get('/api/tnved/' + code)
                .then(response => {
                    commit('SET_RESOLVED', {code, data: response.data});
                    return response.data;
                })
                .catch(() => null);
        },
    },
}
