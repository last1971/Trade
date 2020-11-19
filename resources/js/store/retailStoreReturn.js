import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'retail-store-return';

state.key = 'BACKSHOPCODE';

state.headers = [
    {text: 'Возвращено', value: 'DATATIMEBACK', sortable: true},
    {text: 'Вернул', value: 'USERNAMEBACK', sortable: true},
    {text: 'Категория', value: 'good.category.CATEGORY', sortable: false},
    {text: 'Наименование', value: 'good.name.NAME', sortable: false},
    {text: 'Кол.-во', value: 'QUANSHOP', sortable: true, align: 'right'},
    {text: 'Цена', value: 'PRICE', sortable: false, align: 'right'},
    {text: 'Сумма', value: 'AMOUNT', sortable: true, align: 'right'},
    {text: 'Продано', value: 'DATATIMESALE', sortable: true},
    {text: 'Продал', value: 'USERNAMESALE', sortable: true},
];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
