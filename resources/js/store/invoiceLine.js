import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'invoice-line';

state.key = 'REALPRICECODE';

state.headers = [
    {text: '', value: 'actions', width: 10, sortable: false},
    {
        text: 'Категория',
        value: 'good.category.CATEGORY',
    },
    {
        text: 'Название',
        value: 'good.name.NAME'
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
        value: 'reservesSum',
        align: 'right',
    },
    {
        text: 'Подобрано',
        value: 'pickUpsSum',
        align: 'right',
    },
    {
        text: 'В УПД',
        value: 'transferOutLinesSum',
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
