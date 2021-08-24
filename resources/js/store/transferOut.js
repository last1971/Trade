import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);
let getters = _.cloneDeep(model.getters);

state.name = 'transfer-out';

state.key = 'SFCODE';

state.fillable = ['PRIM', 'SCODE'];

state.headers = [
    {text: '', value: 'actions', width: 10, sortable: false},
    {
        text: 'Дата',
        value: 'DATA',
    },
    {
        text: 'Номер',
        value: 'NSF'
    },
    {
        text: 'Счет',
        value: 'invoice',
    },
    {
        text: 'Покупатель',
        value: 'buyer.SHORTNAME'
    },
    {
        text: 'Строк',
        value: 'transferOutLinesCount',
        align: 'right',
    },
    {
        text: 'Сумма',
        value: 'transferOutLinesSum',
        align: 'right',
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
    return 'УПД № ' + getters.GET(state)(id).NSF + '.pdf'
}

export default {
    namespaced: true,
    state,
    getters,
    mutations: model.mutations,
    actions: model.actions,
}
