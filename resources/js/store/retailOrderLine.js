import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'retail-order-line';

state.key = 'ID';

state.headers = [
    {text: 'Дата', value: 'retailOrder.DATA', sortable: false},
    {text: 'Покупатель', value: 'retailOrder.buyer.SHORTNAME', sortable: false},
    {text: 'Цена', value: 'PRICE', sortable: false, align: 'right'},
    {text: 'Кол.', value: 'QUAN', align: 'right'},
    {text: 'Рез.', value: 'QUAN_RES', sortable: false, align: 'right'},
    {text: 'Пер.', value: 'QUAN_PODB', sortable: false, align: 'right'},
    {text: 'Прд.', value: 'QUAN_SALED', sortable: false, align: 'right'},
];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
