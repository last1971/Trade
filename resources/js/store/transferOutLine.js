import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'transfer-out-line';

state.key = 'REALPRICEFCODE';

state.headers = [
    // {text: '', value: 'actions', width: 10, sortable: false},

];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
