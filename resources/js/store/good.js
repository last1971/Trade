import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'good';

state.key = 'GOODSCODE';

state.fillable = ['NAMECODE', 'CATEGORYCODE', 'UNIT_I', 'BODY', 'PRODUCER', 'PRIM'];

state.headers = [
    {text: '', value: 'actions', width: 10, sortable: false},
    {
        text: 'Категория',
        value: 'category.CATEGORY',
    },
    {
        text: 'Наименование',
        value: 'name.NAME'
    },
    {
        text: 'Корпус',
        value: 'BODY'
    },
    {
        text: 'Производитель',
        value: 'PRODUCER',
    },
    {
        text: 'Цены',
        value: 'retail_price',
        align: 'center',
        sortable: false,
    },
    {
        text: 'Скл/Маг/Своб',
        value: 'warehouse',
        align: 'center',
        sortable: false,
    },
    {
        text: 'Резерв/Нада',
        value: 'reservesQuantity',
        align: 'center',
        sortable: false,
    },
    {
        text: 'В пути/Своб',
        value: 'orderLinesTransitQuantity',
        align: 'center',
        sortable: false,
    },
    {
        text: 'Пороги М/С',
        value: 'order_step',
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
