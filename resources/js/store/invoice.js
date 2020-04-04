import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'invoice';

state.key = 'SCODE';

state.headers = [
    {text: '', value: 'actions', width: 10, sortable: false},
    {
        text: 'Дата',
        value: 'DATA',
    },
    {
        text: 'Номер',
        value: 'NS'
    },
    {
        text: 'Покупатель',
        value: 'buyer.SHORTNAME'
    },
    {
        text: 'Строк',
        value: 'invoiceLinesCount',
        align: 'right',
    },
    {
        text: 'Сумма с НДС',
        value: 'invoiceLinesSum',
        align: 'right',
    },
    {
        text: 'Статус',
        value: 'STATUS'
    },
];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
