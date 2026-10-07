import model from './model'
import _ from 'lodash'
import {getReport, exportXlsx} from '../helpers/reportApi'

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
            return getReport(getters.URL + '/list', payload, commit);
        },
        // JSON детального отчёта по одному товару.
        REPORT({getters, commit}, payload) {
            return getReport(getters.URL + '/report', payload, commit);
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
