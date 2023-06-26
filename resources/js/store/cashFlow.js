import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'cash-flow';

state.key = 'SCHETCODE';

state.fillable = ['MONEYSCHET', 'NS', 'DATA', 'POKUPATCODE', 'NPP', 'SCODE', 'PRIM', 'SFCODE1'];

state.headers = [
    {text: '', value: 'actions', width: 10, sortable: false},
    {
        text: 'Дата',
        value: 'DATA',
    },
    {
        text: 'П/П',
        align: 'right',
        value: 'NPP',
    },
    {
        text: 'Сумма',
        align: 'right',
        value: 'MONEYSCHET'
    },
    {
        text: 'Примечание',
        value: 'PRIM',
    },
    {
        text: 'Кто занес',
        value: 'USERNAME',
    },
    {
        text: 'УПД',
        value: 'transferOut.NSF',
        align: 'center',
        sortable: false,
    },
];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
