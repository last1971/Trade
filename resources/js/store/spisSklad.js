import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'spis-sklad';

state.key = 'SPISSKLADCODE';

state.headers = [
    {text: '', value: 'actions', sortable: false, notHidden: true},
    {text: 'Дата', value: 'DATA'},
    {text: 'Товар', value: 'name.NAME', sortable: false},
    {text: 'Кол.', value: 'QUAN', align: 'right', sortable: false},
    {text: 'Цена', value: 'PRICE', align: 'right', sortable: false},
    {text: 'Кто', value: 'USERNAME', sortable: false},
    {text: 'Причина', value: 'reason.NAME', sortable: false},
    {text: 'Примечание', value: 'PRIM', sortable: false},
];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
