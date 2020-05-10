import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'reserve';

state.key = 'RESERVCODE';

state.headers = [
    {text: 'Дата', value: 'DATA', sortable: false},
    {text: 'Документ', value: 'SCODE', sortable: false},
    {text: 'Покупатель', value: 'PRIM', sortable: false},
    {text: 'Маг.', value: 'QUANSHOP', sortable: false, align: 'right'},
    {text: 'Скл.', value: 'QUANSKLAD', sortable: false, align: 'right'},
];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
