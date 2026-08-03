import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'mark-code';

state.key = 'MARKCODE';

// Сортировка колонок-связей работает через SORT_MAP (markCodeTableMixin)
// и алиасы-джойны в MarkCodeService
state.headers = [
    {text: '', value: 'actions', sortable: false, notHidden: true},
    {text: 'Дата', value: 'CREATED_AT'},
    {text: 'Код', value: 'KI'},
    {text: 'Товар', value: 'good.name.NAME'},
    {text: 'GTIN', value: 'GTIN'},
    {text: 'Серийник', value: 'SERIAL_NUMBER'},
    {text: 'Кол.', value: 'QUANTITY', align: 'right'},
    {text: 'Статус', value: 'STATUS'},
    {text: 'Передан', value: 'TRANSFER_TYPE'},
    {text: 'Счёт', value: 'invoiceLine.invoice.NS'},
    {text: 'УПД', value: 'transferOutLine.transferOut.NSF'},
    {text: 'Приход', value: 'storeLine.NP'},
    {text: 'Заказ', value: 'storeLine.orderLine.MASTER_ID'},
    {text: 'Списание', value: 'spisSklad.DATA', sortable: false},
];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
