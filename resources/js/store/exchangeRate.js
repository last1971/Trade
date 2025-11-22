import _ from 'lodash';
import moment from 'moment';

const state = {
    date: moment().format('Y-MM-DD'),
    rates: [{ CharCode: 'USD', value: 1 }, { CharCode: 'EUR', value: 1 }, { CharCode: 'RUB', value: 1 }],
}

const getters = {
    ALL: state => state.rates,
    GET: state => CharCode => _.find(state.rates, { CharCode }),
    DATE: state => state.date,
    TO_RUB: (state, getters, rootState) => (CharCode, price, sellerId = null) => {
        const rate = getters['GET'](CharCode);
        const priceInRub = rate ? rate.value * price : 0;
        const coefficient = sellerId ? (rootState.CONFIG.priceCoefficients[sellerId] || 1) : 1;
        return priceInRub * coefficient;
    },
    TO_USD: (state, getters) => (CharCode, price) => {
        if (CharCode === 'USD') return price;
        const proxy = getters['TO_RUB'](CharCode, price);
        const usd = getters['GET']('USD');
        return proxy / usd.value;
    }
}

const mutations = {
    SET(state, payload) {
        state.date = payload.date;
        state.rates = payload.rates;
    }
}

const actions = {
    async SET({ state, commit }, date) {
        try {
            const response = await axios.get('/api/exchange-rate', {params: {date}});
            commit('SET', {date, rates: response.data});
        } catch (e) {
            commit('SNACKBAR/ERROR', e.response.data.message, {root: true});
        }
    }
}

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
}
