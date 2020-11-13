import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'retail-sale';

state.key = 'DATATIME';
state.keyType = String;

state.headers = [
    {text: 'Дата', value: 'DATATIME', sortable: true},
    {text: 'Строк', value: 'QUAN', sortable: true, align: 'right'},
    {text: 'Сумма', value: 'SUMMA', sortable: true, align: 'right'},
    {text: 'Покупатель', value: 'SHORTNAME', sortable: true},
    {text: 'Продавец', value: 'USERNAME', sortable: true},
];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
