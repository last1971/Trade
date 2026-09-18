import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

// Магазинная инсталляция читает приходы из SHOPIN: там другой ключ строки, нет ни
// ГТД, ни страны, ни документа поставки (NSF пуст во всей таблице), поэтому эти
// четыре колонки в рознице просто отсутствуют — пустые столбцы не рисуем.
const IS_SHOP = process.env.MIX_IS_ELECTRONICA === 'true';

state.name = 'store-line';

state.key = IS_SHOP ? 'SHOPINCODE' : 'SKLADINCODE';

state.headers = [
    {text: 'Дата', value: 'DATA', sortable: false},
    {text: 'Приход', value: 'NP', align: 'right', sortable: false},
    {text: 'Поставщик', value: 'entry.seller.NAMEPOST', sortable: false},
    ...(IS_SHOP ? [] : [
        {text: 'Дата документа', value: 'DATA_DOC', sortable: false},
        {text: 'Номер', value: 'NDOC', sortable: false},
        {text: 'ГТД', value: 'GTD', sortable: false},
        {text: 'Страна', value: 'STRANA', sortable: false},
    ]),
    {text: 'Кол.', value: 'QUAN', align: 'right', sortable: false},
    {text: 'Цена', value: 'entry.PRICE', align: 'right', sortable: false},
    {text: 'Сумма', value: 'entry.SUMMAP', align: 'right', sortable: false},
];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
