import model from './model'
import _ from 'lodash'
import {getReport, exportXlsx} from '../helpers/reportApi'

let state = _.cloneDeep(model.state);

state.name = 'donor-stock';

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: {
        ...model.actions,
        // JSON подобранного в донорах маркетплейса (тот же сервис, что и xlsx).
        LIST({getters, commit}, payload) {
            return getReport(getters.URL + '/list', payload, commit);
        },
        // Excel того же списка.
        SAVE_LIST({getters, commit}, payload) {
            return exportXlsx(getters.URL + '/export', payload, commit);
        },
    },
}
