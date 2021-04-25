const state = {
    data: [],
    headers: [
        {text: 'Поставщик', value: 'seller', sortable: false},
        {text: 'Название', value: 'name', sortable: false},
        {text: 'Количество', value: 'quantity', sortable: false},
        {text: 'Цена', value: 'price', sortable: false},
        {text: 'Cумма', value: 'amount', sortable: false},
        {text: 'Срок', value: 'deliveryTime', sortable: false},
    ],
    name: 'seller-price',
    quantity: 1,
    isAll: false,
    sellers: [],
    sources: new Map(),
};

const getters = {
    URL: state => `/api/${state.name}`,
    IS_INPUT: (state, getters, rootState, rootGetters) => {
        return rootGetters['AUTH/HAS_PERMISSION']('seller-price.full');
    },
    QUANTITY: state => state.quantity,
    IS_ALL: state => state.isAll,
    HEADERS: state => state.headers,
    SORTED_DATA: (state, getters, rootState, rootGetters) => {
        return _.sortBy(
            state.data,
            (line) => line.orderQuantity * rootGetters['EXCHANGE-RATE/TO_RUB'](line.CharCode, line.price)
        );
    },
    FILTERD_DATA: (state, getters) => {
        return _.filter(getters.SORTED_DATA, (price) => {
            return (price.orderQuantity >= state.quantity || state.isAll) && price.isInput === getters.IS_INPUT;
        })
    },
    RETAIL_PRICE: (state) => (line) => {
        const ret =  _.find(state.data, (price) =>
            !price.isInput
            &&
            price.minQuantity <= state.quantity
            &&
            price.maxQuantity >= state.quantity
            &&
            price.code === line.code
            &&
            price.sellerId === line.sellerId
        );
        return ret ? ret.price : 0;
    },
    SELLERS: state => state.sellers,
}

const mutations = {
    ADD_DATA(state, data) {
        state.data = data.concat(state.data);
    },
    CLEAR_DATA(state) {
        for (let source of state.sources.values()) {
            source.cancel('Cancel request');
        }
        state.sources.clear();
        state.data = [];
    },
    CLEAR_SELLER_DATA(state, sellerId) {
        state.data = _.filter(state.data, (line) => line.sellerId !== sellerId);
    },
    SET_QUANTITY(state, quantity) {
        state.quantity = quantity;
        state.data = state.data.map((line) => {
            const minQuantity = quantity > line.minQuantity ? quantity : line.minQuantity;
            const modulo = minQuantity % line.multiplicity
            const multQuantity = modulo === 0 ? minQuantity : minQuantity - modulo + line.multiplicity;
            line.orderQuantity = multQuantity > line.maxQuantity ? line.maxQuantity : multQuantity;
            return line;
        });
    },
    SET_IS_ALL(state, isAll) {
        state.isAll = !!isAll;
    },
    SET_SELLERS(state, sellers) {
        state.sellers = sellers;
    },
    PUSH_SOURCE(state, payload) {
        state.sources.set(payload.id, payload.source);
    },
    PULL_SOURCE(state, id) {
        state.sources.delete(id);
    },
}

const actions = {
    async GET({state, getters, commit}, params) {
        try {
            const id = _.uniqueId();
            const CancelToken = axios.CancelToken;
            const source = CancelToken.source();
            commit('PUSH_SOURCE', { id, source });
            const response = await axios.get(getters.URL, { params, cancelToken: source.token });
            const data = response.data.data.map((line) => {
                line.isCache = response.data.cache;
                line.maxQuantity = line.maxQuantity || line.quantity;
                line.maxQuantity = line.quantity > line.maxQuantity ? line.quantity : line.maxQuantity;
                line.orderQuantity = 1000000;
                return line;
            });
            commit('ADD_DATA', data);
            commit('SET_QUANTITY', state.quantity);
            commit('PULL_SOURCE', id);
        } catch (e) {
            if (!axios.isCancel(e)) {
                commit('SNACKBAR/ERROR', e.response.data.message, {root: true});
            }
        }
    },
    async GET_SELLERS({commit, getters}) {
        try {
            const response = await axios.get(getters.URL + '/sellers');
            commit('SET_SELLERS', response.data.map((v) => {
                v.selected = { isApi: true, isFile: true }
                v.loading = { isApi: false, isFile: false }
                return v;
            }));
        } catch (e) {
            commit('SNACKBAR/ERROR', e.response.data.message, {root: true});
        }
    },
}

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions
}
