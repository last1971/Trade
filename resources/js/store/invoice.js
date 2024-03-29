import model from './model'
import _ from 'lodash'
import moment from 'moment'

let state = _.cloneDeep(model.state);

let getters = _.cloneDeep(model.getters);

let mutations = _.cloneDeep(model.mutations);

state.name = 'invoice';

state.key = 'SCODE';

state.items = [
    { SCODE: 0, FIRM_ID: 38, DATA: moment().format('Y-MM-DD'), STATUS: 0, FIRMS_HISTORY_ID: 7 }
]

state.fillable = ['DATA', 'NS', 'NZ', 'FIRM_ID', 'FIRMS_HISTORY_ID', 'POKUPATCODE', 'PRIM', 'STATUS', 'IGK', 'STAFF_ID'];

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
        text: 'Заявка',
        value: 'NZ'
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

state.currentInvoice = null;

getters.PDF = state => id => {
    return 'Счет № ' + getters.GET(state)(id).NS + '.pdf'
}

getters['GET-CURRENT'] = state => state.currentInvoice;

mutations['SET-CURRENT'] = function (state, currentInvoice) {
    state.currentInvoice = currentInvoice;
}

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions: model.actions,
}
