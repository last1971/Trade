import Vue from 'vue'
import { mpnCardKey } from '../helpers/mpn';

// Справочник деталей mpn.cc. Долгий кэш держит сервис (Redis, найденное 30 дней),
// здесь — только то, что человек уже открывал в этой сессии, чтобы повторный
// клик по той же строке не ходил на сервер.
const state = {
    cards: {},
    loading: false,
    // {message, blockedUntil} — blockedUntil заполнен, когда mpn.cc нас притормозил (503).
    error: null,
    dialog: false,
    current: null,
};

const getters = {
    URL: () => '/api/seller-price/mpn',
    IS_OPEN: state => state.dialog,
    IS_LOADING: state => state.loading,
    ERROR: state => state.error,
    CURRENT: state => state.current,
    CARD: state => state.current ? state.cards[state.current.key] : null,
};

const mutations = {
    // Vue.set — ключи карточек появляются на лету, прямое присваивание нереактивно (Vue 2).
    SET_CARD(state, {key, card}) {
        Vue.set(state.cards, key, card);
    },
    SET_LOADING(state, loading) {
        state.loading = !!loading;
    },
    SET_ERROR(state, error) {
        state.error = error;
    },
    OPEN(state, {q, manufacturer}) {
        state.current = {q, manufacturer: manufacturer || null, key: mpnCardKey(q, manufacturer)};
        state.error = null;
        state.dialog = true;
    },
    CLOSE(state) {
        state.dialog = false;
        state.current = null;
        state.error = null;
    },
};

const actions = {
    /** Клик по иконке справки: открыть диалог и подтянуть карточку, если её ещё нет. */
    async OPEN_CARD({state, commit, dispatch}, {q, manufacturer}) {
        commit('OPEN', {q, manufacturer});
        if (!state.cards[state.current.key]) await dispatch('GET', {q, manufacturer});
    },

    /** Кнопка «Обновить» в диалоге — тот же запрос, но мимо кэша сервиса. */
    async REFRESH({state, dispatch}) {
        if (!state.current) return;
        await dispatch('GET', {...state.current, refresh: true});
    },

    async GET({commit, getters}, {q, manufacturer, refresh}) {
        commit('SET_LOADING', true);
        commit('SET_ERROR', null);
        try {
            const response = await axios.get(getters.URL, {
                params: _.omitBy({q, manufacturer, refresh: refresh ? 1 : null}, _.isNil),
            });
            commit('SET_CARD', {key: mpnCardKey(q, manufacturer), card: response.data});
        } catch (e) {
            const data = e.response ? e.response.data : null;
            // 503 — mpn.cc нас притормозил, в теле время, до которого он молчит.
            commit('SET_ERROR', {
                message: (data && (data.message || data.error)) || 'Справочник mpn.cc недоступен',
                blockedUntil: data ? data.blockedUntil : null,
            });
        } finally {
            commit('SET_LOADING', false);
        }
    },
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
}
