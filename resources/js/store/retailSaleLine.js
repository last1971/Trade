import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

let actions = _.cloneDeep(model.actions);

state.name = 'retail-sale-line';

state.key = 'SHOPLOGCODE';
state.keyType = String;

state.headers = [
    {text: 'Категория', value: 'good.category.CATEGORY', sortable: false},
    {text: 'Наименование', value: 'good.name.NAME', sortable: true},
    {text: 'Корпус', value: 'good.BODY', sortable: false},
    {text: 'Производитель', value: 'good.PRODUCER', sortable: false},
    {text: 'Кол.-во', value: 'QUANSHOP', sortable: true, align: 'right'},
    {text: 'Цена', value: 'PRICE', sortable: true, align: 'right'},
    {text: 'Сумма', value: 'AMOUNT', sortable: true, align: 'right'},
    {text: 'Скидка', value: 'DISCOUNT', sortable: true, align: 'right'},
];

actions.REFUND = async ({state, getters, commit}, payload) => {
    await axios.delete(getters.URL, { data: payload });
    payload.selectedIds.forEach((id) => commit('REMOVE', id) );
}

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions,
}
