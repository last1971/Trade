import _ from 'lodash';
import moment from 'moment';

const state = {
    date: moment().format('Y-MM-DD'),
    rates: [{ CharCode: 'USD', value: 1 }, { CharCode: 'EUR', value: 1 }],
}

const getters = {
    ALL: state => state.rates,
    GET: state => CharCode => _.find(state.rates, { CharCode }),
    DATE: state => state.date,
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
