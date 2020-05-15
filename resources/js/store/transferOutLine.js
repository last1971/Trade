import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'transfer-out-line';

state.key = 'REALPRICEFCODE';

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
        text: 'Ед.',
        value: 'good.UNIT_I',
    },
    {
        text: 'Кол.-во',
        value: 'QUAN',
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
    {
        text: 'Страна',
        value: 'STRANA',
    },
    {
        text: 'ГТД',
        value: 'GTD',
    },

];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
