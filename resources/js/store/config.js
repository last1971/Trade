const state = {
    priceCoefficients: {}
};

const getters = {
    PRICE_COEFFICIENTS: state => state.priceCoefficients,
    PRICE_COEFFICIENT: state => sellerId => state.priceCoefficients[sellerId] || 1,
};

const mutations = {
    SET_PRICE_COEFFICIENTS(state, coefficients) {
        state.priceCoefficients = coefficients;
    }
};

const actions = {
    async LOAD({ commit }) {
        const response = await axios.get('/api/config');
        commit('SET_PRICE_COEFFICIENTS', response.data.priceCoefficients);
    }
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
}
