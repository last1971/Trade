import _ from 'lodash';

const state = {
    items: [],
    isReatailStore: false,
    isStore: false,
    buyerId: null,
    opened: false,

    headers: [
        {text: '', value: 'actions', width: 10, sortable: false},
        {
            text: 'Категория',
            value: 'good.category.CATEGORY',
        },
        {
            text: 'Наименование',
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
            value: 'quantity',
            align: 'right',
        },
        {
            text: 'Цена',
            value: 'price',
            align: 'right',
        },
        {
            text: 'Скидка',
            value: 'discount',
            align: 'right',
        },
        {
            text: 'Сумма',
            value: 'amount',
            align: 'right',
        },
    ]
}

const getters = {
    ALL: (state, getters, rootState, rootGetters) => {
        return state.items.map((line) => {
            return Object.assign(line, { good: rootGetters['GOOD/GET'](line.GOODSCODE)});
        });
    },
    'BUYER-ID': state => state.buyerId,
    BUYER: (state, getters, rootState, rootGetters) => {
        return rootGetters['BUYER/GET'](state.buyerId);
    },
    HEADERS: state => state.headers,
    OPENED: state => state.opened,
    'IS-RETAIL-STORE': state => state.isReatailStore,
    'TOTAL-COUNT': state => state.items.length,
    'TOTAL-AMOUNT': state => state.items.reduce((a,v) => a + v.amount, 0),
}

const mutations = {
    PUSH(state, payload) {
        state.items.push(payload);
    },
    CHANGE(state, payload) {
        const GOODSCODE = { payload };
        const index = _.findIndex(state.items, { GOODSCODE });
        state.items.splice(index, 1, payload);
    },
    OPENED(state, payload) {
        if (!payload) {
            state.items = [];
            state.opened = false;
            state.buyerId = null;
        } else {
            state.opened = true;
        }

    },
    'BUYER-ID'(state, payload) {
        state.buyerId = payload;
    },
    'IS-RETAIL-STORE'(state, payload) {
        state.isReatailStore = payload;
    },
    'APPLAY-DISCOUNT'(state) {
        const items = _.cloneDeep(state.items);
        state.items = items.map((item) => {
            item.price = this.getters['GOOD/PRICE_WITH_DISCOUNT'](item.GOODSCODE, item.quantity);
            item.amount = item.price * item.quantity;
            return item;
        })
    }
}

const actions = {}

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions
}


