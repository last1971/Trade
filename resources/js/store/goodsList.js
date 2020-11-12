import _ from 'lodash';

const state = {
    items: [],
    isReatailStore: false,
    isStore: false,
    buyerId: null,
    opened: false,
    renderKey: 1,

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
            return Object.assign({ good: rootGetters['GOOD/GET'](line.GOODSCODE)}, line);
        });
    },
    'BUYER-ID': state => state.buyerId,
    BUYER: (state, getters, rootState, rootGetters) => {
        return rootGetters['BUYER/GET'](state.buyerId);
    },
    GET: state => GOODSCODE => _.find(state.items, { GOODSCODE }),
    HEADERS: state => state.headers,
    OPENED: state => state.opened,
    'IS-RETAIL-STORE': state => state.isReatailStore,
    'TOTAL-COUNT': state => state.items.length,
    'TOTAL-AMOUNT': state => state.items.reduce((a,v) => a + v.amount, 0),
    'RENDER-KEY': state => state.renderKey,
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
    'CHANGE-QUANTITY'(state, payload) {
        const { GOODSCODE, quantity } = payload;
        const index = _.findIndex(state.items, { GOODSCODE });
        const change = _.cloneDeep(state.items[index]);
        change.quantity = quantity;
        change.price = this.getters['GOOD/PRICE_WITH_DISCOUNT'](GOODSCODE, quantity);
        change.discount = this.getters['GOOD/DISCOUNT'](GOODSCODE, change.price);
        change.amount = change.quantity * change.price;
        state.items.splice(index, 1, change);
    },
    REMOVE(state, payload) {
        const GOODSCODE = typeof payload === 'object' ? payload.GOODSCODE : parseInt(payload);
        const index = _.findIndex(state.items, { GOODSCODE });
        state.items.splice(index, 1);
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
            item.discount = this.getters['GOOD/DISCOUNT'](item.GOODSCODE, item.price)
            item.amount = item.price * item.quantity;
            return item;
        })
    },
    'RENDER'(state) {
        state.renderKey += 1;
    }
}

const actions = {
    async SALE({state, rootGetters}, paymentType = 'cash') {
        await axios.post(
            '/api/goods-list',
            {
                lines: state.items.map((item) => {
                    return Object.assign({ name: rootGetters['GOOD/GET'](item.GOODSCODE).name.NAME }, item);
                }),
                buyerId: state.buyerId,
                paymentType,
            }
        );
    }
}

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions
}


