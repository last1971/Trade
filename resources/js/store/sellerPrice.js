const state = {
    data: [],
    headers: [
        {text: 'Поставщик', value: 'seller', sortable: false},
        {text: 'Название', value: 'name', sortable: false},
        {text: 'Количество', value: 'quantity', sortable: false},
        {text: 'Цена', value: 'price', sortable: false},
        {text: 'Срок', value: 'deliveryTime', sortable: false},
        {text: 'Заказ', value: 'seller', sortable: false},
    ],
    name: 'seller-price',
    quantity: 1,
    isAll: false,
    sellers: [],
};

const getters = {
    URL: state => `/api/${state.name}`,
    IS_INPUT: (state, getters, rootState, rootGetters) => {
        return rootGetters['AUTH/HAS_PERMISSION']('seller-price.full');
    },
    QUANTITY: state => state.quantity,
    IS_ALL: state => state.isAll,
    HEADERS: state => state.headers,
    SORTED_DATA: state => {
        return _.sortBy(state.data, 'amount');
    },
    FILTERD_DATA: (state, getters) => {
        return _.filter(getters.SORTED_DATA, (price) => {
            return (price.quantity >= state.quantity || state.isAll) && price.isInput === getters.IS_INPUT;
        })
    },
    RETAIL_PRICE: (state) => (line) => {
        return  _.find(state.data, (price) =>
            !price.isInput
            &&
            price.minQuantity <= state.quantity
            &&
            price.maxQuantity >= state.quantity
            &&
            price.code === line.code
            &&
            price.sellerId === line.sellerId
        ) || 0;
    },
    SELLERS: state => state.sellers,
}

const mutations = {
    SET_DATA(state, data) {
        state.data = data;
    },
    SET_QUANTITY(state, quantity) {
        state.quantity = quantity;
        state.data = state.data.map((line) => {
            line.amount = line.price * quantity;
            return line;
        });
    },
    SET_IS_ALL(state, isAll) {
        state.isAll = !!isAll;
    },
    SET_SELLERS(state, sellers) {
        state.sellers = sellers;
    }
}

const actions = {
    async GET({state, getters, commit, rootGetters}, params) {
        try {
            const response = await axios.get(getters.URL, {params});
            const data = response.data.data.map((line) => {
                line.isCache = response.data.cache;
                line.price = line.price * rootGetters['EXCHANGE-RATE/GET'](line.CharCode).value;
                line.amount = line.price * state.quantity;
                return line;
            });
            commit('SET_DATA', data);
        } catch (e) {
            commit('SNACKBAR/ERROR', e.response.data.message, {root: true});
        }
    },
    async GET_SELLERS({commit}) {
        try {
            const response = await axios.get(getters.URL + '/sellers');
            commit('SET_SELLERS', response.data)
        } catch (e) {
            commit('SNACKBAR/ERROR', e.response.data.message, {root: true});
        }
    }
}

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions
}
