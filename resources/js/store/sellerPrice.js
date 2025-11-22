import seller from "./seller";

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
    sellers: localStorage.getItem('isApiSeller') ? JSON.parse(localStorage.getItem('isApiSeller')) : [],
    sources: new Map(),
    selectedSellerId: null,
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
            (line) => line.orderQuantity * rootGetters['EXCHANGE-RATE/TO_RUB'](line.CharCode, line.price, line.sellerId)
        );
    },
    FILTERD_DATA: (state, getters) => {
        return _.filter(getters.SORTED_DATA, (price) => {
            return (
                    (
                        price.orderQuantity >= state.quantity
                        &&
                        price.orderQuantity <= price.quantity
                        &&
                        price.minQuantity <= price.maxQuantity
                    )
                    ||
                    state.isAll
                )
                &&
                (state.selectedSellerId === null || state.selectedSellerId === price.sellerId)
                &&
                price.isInput === getters.IS_INPUT;
        })
    },
    RETAIL_PRICE: (state) => (line) => {
        const ret =  _.find(state.data, (price) => {
            //if (price.minQuantity <= state.quantity) debugger;
            return    !price.isInput
                &&
                price.minQuantity <= line.orderQuantity //state.quantity
                &&
                price.maxQuantity >= line.orderQuantity //state.quantity
                &&
                price.code === line.code
                &&
                price.sellerId === line.sellerId
                &&
                price.warehouseCode === line.warehouseCode
            }
        );
        return ret ? ret.price : 0;
    },
    SELLERS: state => _.sortBy(state.sellers, 'name'),
    SELLER_LINES_QUANTITY: state => sellerId => {
        return _.uniqBy(_.filter(state.data, {sellerId}), 'code').length;
    },
    SELECTED_SELLER_ID: state => state.selectedSellerId,
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
    SAVE_SELLERS(state) {
        localStorage.setItem('isApiSeller', JSON.stringify(state.sellers));
    },
    SET_SELLER_API_ERROR(state, sellerId) {
        const index = _.findIndex(state.sellers, { sellerId });
        const seller = state.sellers[index];
        seller.isApiError = true;
        state.sellers.splice(index, 1, seller)
    },
    SELLER_SELECT(state, sellerId) {
        state.selectedSellerId = state.selectedSellerId === sellerId ? null : sellerId;
    },
    CLEAR_SELLER_API_ERROR(state, sellerId) {
        const index = _.findIndex(state.sellers, { sellerId });
        const seller = state.sellers[index];
        seller.isApiError = false;
        state.sellers.splice(index, 1, seller)
    },
    CLEAR_ALL_API_ERRORS(state) {
        state.sellers = state.sellers.map((seller) => {
            seller.isApiError = false;
            return seller;
        });
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
//                line.maxQuantity = line.quantity > line.maxQuantity ? line.quantity : line.maxQuantity;
                line.orderQuantity = 1000000;
                return line;
            });
            commit('ADD_DATA', data);
            commit('SET_QUANTITY', state.quantity);
            if (response.data.isApiError) {
                commit('SET_SELLER_API_ERROR', params.sellerId);
            }
            commit('PULL_SOURCE', id);
        } catch (e) {
            if (!axios.isCancel(e)) {
                commit('SNACKBAR/ERROR', e.response.data.message, {root: true});
            }
        }
    },
    async GET_SELLERS({state, commit, getters}) {
        try {
            const response = await axios.get(getters.URL + '/sellers');
            if (state.sellers.length !== response.data.length) {
                commit('SET_SELLERS', response.data.map((v) => {
                    //v.selected = { isApi: true, isFile: true }
                    v.loading = false
                    v.isApiError = false;
                    return v;
                }));
            }
        } catch (e) {
            commit('SNACKBAR/ERROR', e.response.data.message, {root: true});
        }
    },
    async SAVE_SELLER_PRICE({state, getters}, item) {
        axios.put('/api/seller-good/' + item.sellerGoodId, _.pick(item, ['goodId']));
    }
}

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions
}
