import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'buyer-debt';

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: {
        ...model.actions,
        // JSON-отчёт для показа на странице (тот же сервис, что и SAVE/export).
        REPORT({getters, commit}, payload) {
            return axios.get(getters.URL + '/report', {params: payload})
                .then(response => response.data)
                .catch(error => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    throw error;
                });
        },
    },
}
