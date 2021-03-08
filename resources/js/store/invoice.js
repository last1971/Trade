import model from './model'
import _ from 'lodash'
import moment from 'moment'

let state = _.cloneDeep(model.state);

let getters = _.cloneDeep(model.getters);

state.name = 'invoice';

state.key = 'SCODE';

state.items = [
    { SCODE: 0, FIRM_ID: 38, DATA: moment().format('Y-MM-DD'), STATUS: 0 }
]

state.fillable = ['DATA', 'NS', 'FIRM_ID', 'POKUPATCODE', 'PRIM', 'STATUS', 'IGK'];

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
    {
        text: 'Примечание',
        value: 'PRIM'
    },
    {
        text: 'ИГК',
        value: 'IGK'
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
