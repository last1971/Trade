import model from './model'
import _ from 'lodash'
import FileSaver from 'file-saver'

let state = _.cloneDeep(model.state);

state.name = 'replenish';

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: {
        ...model.actions,
        // JSON массового отчёта «что закупить» (тот же сервис, что и xlsx).
        LIST({getters, commit}, payload) {
            return axios.get(getters.URL + '/list', {params: payload})
                .then(response => response.data)
                .catch(error => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    throw error;
                });
        },
        // JSON детального отчёта по одному товару.
        REPORT({getters, commit}, payload) {
            return axios.get(getters.URL + '/report', {params: payload})
                .then(response => response.data)
                .catch(error => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    throw error;
                });
        },
        // Excel массового отчёта (тот же сервис, что и LIST).
        SAVE_LIST({getters, commit}, payload) {
            return exportXlsx(getters.URL + '/list-export', payload, commit);
        },
        // Excel детального отчёта (тот же сервис, что и REPORT).
        SAVE_REPORT({getters, commit}, payload) {
            return exportXlsx(getters.URL + '/report-export', payload, commit);
        },
    },
}

// Скачивание xlsx с заданным именем файла (как SAVE в model.js, но с указанием url).
function exportXlsx(url, payload, commit) {
    const query = _.cloneDeep(payload);
    const filename = query.filename;
    delete query.filename;

    return axios.get(url, {params: query, responseType: 'blob'})
        .then(response => {
            FileSaver.saveAs(response.data, filename || 'replenish.xlsx');
            return response;
        })
        .catch(error => {
            commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
            throw error;
        });
}
