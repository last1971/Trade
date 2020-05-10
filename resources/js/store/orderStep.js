import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.dependentModels = {GOOD: 'orderStep'}

state.fillable = [
    'GOODSCODE',
    'BOUND_QUAN_SKLAD',
    'BOUND_QUAN_SHOP',
    'QUAN_TO_ZAKAZ_SKLAD',
    'QUAN_TO_ZAKAZ_SHOP',
    'USERNAME',
    'DATA'
];

state.name = 'order-step';

state.key = 'ID';

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
