// Справочник маркируемых ТН ВЭД → ОКПД2. Единственный источник на фронте —
// ручка /api/marking/dict (бэк MarkingDictService). Тянется один раз за сессию
// и переиспользуется карточкой, массовым разбором и страницей-ревью.

// Промис загрузки кодов маркируемых товаров, пока она в полёте (см. FETCH_GOODS).
let markGoodsPromise = null;

// Значок «товар маркируется»: геттер/иконка/подпись одним объектом.
// Единственный источник для ChzMark и крошек карточек (формат crumbIcon в Model.vue).
export const chzIcon = {getter: 'MARKING/IS_MARK_GOOD', name: '$chz', title: 'Маркировка ЧЗ'};
export default {
    namespaced: true,
    state: {
        dict: [],
        loaded: false,
        // Кэш расшифровок кодов ТНВЭД: code → {found, name, tariff, mark_required, okpd2}.
        resolved: {},
        // GOODSCODE товаров, подлежащих маркировке (/api/marking/goods) — для значка ЧЗ.
        markGoods: new Set(),
        markGoodsLoaded: false,
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
        // Подлежит ли товар маркировке (для значка ЧЗ у названия).
        // markGoodsLoaded в замыкании — чтобы Vue пересчитал после загрузки Set.
        IS_MARK_GOOD: state => code => state.markGoodsLoaded && state.markGoods.has(Number(code)),
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
        SET_MARK_GOODS(state, codes) {
            state.markGoods = new Set(codes.map(Number));
            state.markGoodsLoaded = true;
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
        // Загрузить коды маркируемых товаров один раз; повторные вызовы — no-op.
        // Промис в полёте общий: GoodName рендерится сотнями за кадр,
        // без него первая отрисовка таблицы дала бы шквал одинаковых запросов.
        FETCH_GOODS({state, commit}) {
            if (state.markGoodsLoaded) {
                return Promise.resolve(state.markGoods);
            }
            if (!markGoodsPromise) {
                markGoodsPromise = axios.get('/api/marking/goods')
                    .then(response => {
                        commit('SET_MARK_GOODS', response.data);
                        return state.markGoods;
                    });
            }
            return markGoodsPromise;
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
