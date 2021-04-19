import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'invoice-line';

state.key = 'REALPRICECODE';

state.fillable = ['SCODE', 'GOODSCODE', 'QUAN', 'PRICE', 'SUMMAP', 'PRIM', 'WHERE_ORDERED'];

state.headers = [
     {text: '', value: 'actions', width: 10, sortable: false},
    {
        text: 'Дата',
        value: 'invoice.DATA',
        notInvoiceLines: true,
    },
    {
        text: 'Счет',
        value: 'invoice.NS',
        notInvoiceLines: true,
    },
    {
        text: 'Покупатель',
        value: 'invoice.buyer.SHORTNAME',
        sortable: false,
        notInvoiceLines: true,
    },
    {
        text: 'Категория',
        value: 'category.CATEGORY',
    },
    {
        text: 'Название',
        value: 'name.NAME'
    },
    {
        text: 'Корпус',
        value: 'good.BODY'
    },
    {
        text: 'Производитель',
        value: 'good.PRODUCER',
    },
    {
        text: 'Ед.',
        value: 'good.UNIT_I',
    },
    {
        text: 'Кол.-во',
        value: 'QUAN',
        align: 'right',
    },
    {
        text: 'Резерв',
        value: 'reservesQuantity',
        align: 'right',
    },
    {
        text: 'Путь',
        value: 'orderLinesTransitQuantity',
        align: 'right',
    },
    {
        text: 'Подобрано',
        value: 'pickUpsQuantity',
        align: 'right',
    },
    {
        text: 'В УПД',
        value: 'transferOutLinesQuantity',
        align: 'right',
    },
    {
        text: 'Цена без НДС',
        value: 'priceWithoutVat',
        align: 'right',
    },
    {
        text: 'Цена',
        value: 'PRICE',
        align: 'right',
    },
    {
        text: 'Сумма без НДС',
        value: 'sumWithoutVat',
        align: 'right',
    },
    {
        text: 'Сумма',
        value: 'SUMMAP',
        align: 'right',
    },
    {
        text: 'Срок',
        value: 'PRIM',
    },
    {
        text: 'Откуда',
        value: 'WHERE_ORDERED',
        full: true,
    },
];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
