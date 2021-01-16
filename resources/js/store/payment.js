import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'payment';

state.key = 'id';

state.fillable = ['user_id', 'seller_id', 'number', 'date', 'amount', 'weight', 'pay_before', 'comment']

state.headers = [
    {text: 'Дата', value: 'date', sortable: true},
    {text: 'Номер', value: 'number', sortable: false},
    {text: 'Поставшик', value: 'seller.NAMEPOST', sortable: true},
    {text: 'Сумма', value: 'amount', sortable: true, align: 'right'},
    {text: 'Оплачено', value: 'paid', sortable: true, align: 'right'},
    {text: 'Порядок', value: 'weight', sortable: true, align: 'right'},
    {text: 'Оплата до', value: 'pay_before', sortable: true},
    {text: 'Примечание', value: 'comment', sortable: false},
    {text: 'Пользователь', value: 'user.name', sortable: false}
];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
