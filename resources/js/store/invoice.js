import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

let getters = _.cloneDeep(model.getters);

state.name = 'invoice';

state.key = 'SCODE';

state.fillable = ['DATA', 'NS', 'FIRM_ID', 'POKUPATCODE', 'PRIM', 'STATUS'];

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
        text: 'Сумма',
        value: 'invoiceLinesSum',
        align: 'right',
    },
    {
        text: 'Оплачено',
        value: 'cashFlowsSum',
        align: 'right',
    },
    {
        text: 'Отгружено',
        value: 'transferOutLinesSum',
        align: 'right',
    },
    {
        text: 'Статус',
        value: 'STATUS'
    },
    {
        text: 'Фирма',
        value: 'firm.FIRMNAME'
    },
    {
        text: 'Манагер',
        value: 'employee.FULLNAME'
    },
];

getters.PDF = state => id => {
    return 'Счет № ' + getters.GET(state)(id).NS + '.pdf'
}


export default {
    namespaced: true,
    state,
    getters,
    mutations: model.mutations,
    actions: model.actions,
}
