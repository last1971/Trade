import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'order';

state.key = 'ID';

state.fillable = ['WHEREISPOSTCODE', 'INVOICE_NUM', 'INVOICE_DATA', 'DATA_PRIH', 'PRIM', 'STATUS'];

state.headers = [
    //text: '', value: 'actions', width: 10, sortable: false},
    {
        text: 'Наш номер',
        value: 'NZAKAZ',
    },
    {
        text: 'Дата',
        value: 'INVOICE_DATA',
    },
    {
        text: 'Номер',
        value: 'INVOICE_NUM'
    },
    {
        text: 'Поставшик',
        value: 'seller.NAMEPOST'
    },
    {
        text: 'Приходит',
        value: 'DATA_PRIH',
    },
    {
        text: 'Строк',
        value: 'orderLinesCount',
        align: 'right',
    },
    {
        text: 'Сумма',
        value: 'orderLinesSum',
        align: 'right',
    },
    {
        text: 'Оплачено',
        value: 'cashFlowsSum',
        align: 'right',
    },
    {
        text: 'Поступило',
        value: 'INSUM',
        align: 'right',
        sortable: false,
    },
    {
        text: 'Статус',
        value: 'STATUS'
    },
    {
        text: 'Манагер',
        value: 'employee.FULLNAME'
    },
];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
