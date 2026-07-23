// Справочник маркируемых ТН ВЭД → ОКПД2. Единственный источник на фронте —
// ручка /api/marking/dict (бэк MarkingDictService). Тянется один раз за сессию
// и переиспользуется карточкой, массовым разбором и страницей-ревью.
export default {
    namespaced: true,
    state: {
        dict: [],
        loaded: false,
    },
    getters: {
        // Весь справочник: [{c, n, o:[{c, n}]}].
        DICT: state => state.dict,
        // Варианты ОКПД2 для кода (пусто, если код не маркируется).
        OKPD2_OPTIONS: state => code => {
            const entry = state.dict.find(t => t.c === code);
            return entry ? entry.o : [];
        },
    },
    mutations: {
        SET(state, dict) {
            state.dict = dict;
            state.loaded = true;
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
    },
}
