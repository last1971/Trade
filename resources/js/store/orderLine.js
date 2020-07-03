import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'order-line';

state.key = 'ID';

state.fillable = ['MASTER_ID', 'GOODSCODE', 'QUAN', 'NAME_IN_PRICE', 'GTD', 'STRANA', 'STAFF_ID', 'PRIM'];

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
        text: 'Поступило',
        value: 'inQuantity',
        align: 'right',
        sortable: false,
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
        text: 'Страна',
        value: 'STRANA'
    },
    {
        text: 'ГТД',
        value: 'GTD'
    },
    {
        text: 'Ожидаем',
        value: 'DATA_PRIH'
    },
    {
        text: 'Примечание',
        value: 'PRIM'
    },
];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
