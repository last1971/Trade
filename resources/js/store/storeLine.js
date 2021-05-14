import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'store-line';

state.key = 'SKLADINCODE';

state.headers = [
    {text: 'Дата', value: 'DATA', sortable: false},
    {text: 'Приход', value: 'NP', align: 'right', sortable: false},
    {text: 'Дата документа', value: 'DATA_DOC', sortable: false},
    {text: 'Номер', value: 'NDOC', sortable: false},
    {text: 'Кол.', value: 'QUAN', align: 'right', sortable: false},
    {text: 'Цена', value: 'entry.PRICE', align: 'right', sortable: false},
    {text: 'Сумма', value: 'entry.SUMMAP', align: 'right', sortable: false},
];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
