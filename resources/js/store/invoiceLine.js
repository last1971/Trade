import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'invoice-line';

state.key = 'REALPRICECODE';

state.headers = [
    // {text: '', value: 'actions', width: 10, sortable: false},
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
        text: 'Цена',
        value: 'PRICE',
        align: 'right',
    },
    {
        text: 'Сумма',
        value: 'SUMMAP',
        align: 'right',
    },
];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}