import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'seller-order';

state.key = 'id';

// state.fillable = ['NAMEPOST', 'EMAIL', 'INN'];

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
