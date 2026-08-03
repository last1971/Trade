import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'store-in';

state.key = 'NP';

state.headers = [
    {text: '', value: 'actions', sortable: false, notHidden: true},
    {text: 'Дата', value: 'DATA'},
    {text: 'Приход', value: 'NP', align: 'right'},
    {text: 'Поставщик', value: 'seller.NAMEPOST', sortable: false},
    {text: 'Документ', value: 'NDOC', sortable: false},
    {text: 'Дата документа', value: 'DATA_DOC'},
    {text: 'Строк', value: 'LINES_COUNT', align: 'right', sortable: false},
    {text: 'Кол.', value: 'QUAN', align: 'right', sortable: false},
    {text: 'Сумма', value: 'SUMMAP', align: 'right'},
    {text: '', value: 'data-table-expand'},
];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
