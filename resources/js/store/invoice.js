import model from './model'
import _ from 'lodash'
import moment from 'moment'
import createLocalStorageSync from '../helpers/localStorage';
const currentInvoiceStorage = createLocalStorageSync('current_invoice_id');

let state = _.cloneDeep(model.state);

let getters = _.cloneDeep(model.getters);

let mutations = _.cloneDeep(model.mutations);

state.name = 'invoice';

state.key = 'SCODE';

state.items = [];

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

state.currentInvoice = currentInvoiceStorage.get(null);

getters['GET'] = (state, getters) => (id) => {
    if (id === 0 || id === '0') {
        return getters['GET_NEW_INVOICE'];
    }
    return model.getters.GET(state)(id);
};

getters['GET_NEW_INVOICE'] = () => {
    return {
        SCODE: 0,
        FIRM_ID: 38,
        DATA: moment().format('Y-MM-DD'),
        STATUS: 0,
        FIRMS_HISTORY_ID: 7
    };
};

getters.PDF = state => (id, documentType) => {
    const invoice = getters.GET(state)(id);
    if (documentType === 'specification') {
        return 'Спецификация № ' + invoice.NS + '.pdf';
    }
    return 'Счет № ' + invoice.NS + '.pdf';
}

getters['GET-CURRENT'] = state => state.currentInvoice;

mutations['SET-CURRENT'] = function (state, currentInvoice) {
    state.currentInvoice = currentInvoice;
    currentInvoiceStorage.set(currentInvoice);
}

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions: model.actions,
}
