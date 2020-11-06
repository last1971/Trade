import model from './model'
import _ from 'lodash'

const state = _.cloneDeep(model.state);
const getters = _.cloneDeep(model.getters);

getters.PRICE_WITH_DISCOUNT = (state, getters, rootState, rootGetters) => (id, quantity) => {
    const good = getters['GET'](id);
    if (good && good.retailPrice) {
        const buyerDiscount = parseFloat(good.retailPrice.PRICEROZN)
            * (100 - (rootGetters['GOODS-LIST/BUYER'] ?
                parseFloat(rootGetters['GOODS-LIST/BUYER'].SUMMA_PRICE_1) : 0)) / 100;
        let goodDiscount = parseFloat(good.retailPrice.PRICEROZN);
        if (quantity >= good.retailPrice.QUANMOPT && good.retailPrice.QUANMOPT > 0) {
            goodDiscount = parseFloat(good.retailPrice.PRICEMOPT);
        }
        if (quantity >= good.retailPrice.QUANOPT && good.retailPrice.QUANOPT > 0) {
            goodDiscount = parseFloat(good.retailPrice.PRICEOPT);
        }
        return _.min([ buyerDiscount, goodDiscount ]);
    }
    return 0;
}

getters.DISCOUNT = (state, getters) => (id, price) => {
    const good = getters['GET'](id);
    return good && good.retailPrice ? (1 - price / good.retailPrice.PRICEROZN) * 100 : 0;
}

state.items = [
    {
        GOODSCODE: 0,
        NAMECODE: null,
        YEARP: '-',
        PRIM: '-',
    }
];

state.name = 'good';

state.key = 'GOODSCODE';

state.fillable = ['NAMECODE', 'CATEGORYCODE', 'UNIT_I', 'BODY', 'PRODUCER', 'PRIM', 'YEARP'];

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
        value: 'retailPrice',
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
        value: 'orderStep',
        align: 'center',
        sortable: false,
    },
];

export default {
    namespaced: true,
    state,
    getters,
    mutations: model.mutations,
    actions: model.actions,
}
