import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'payment-order';

state.key = 'id';

state.fillable = ['payment_id', 'number', 'date', 'amount', ]

state.headers = [
    {text: 'Дата', value: 'date', sortable: true},
    {text: 'Номер', value: 'number', sortable: false},
    {text: 'Сумма', value: 'amount', sortable: true, align: 'right'},
];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
