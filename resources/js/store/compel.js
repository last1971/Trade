const state = {
    deliveryModes: [],
    deliveryModesLoaded: false
};

const getters = {
    GET_DELIVERY_MODES: state => state.deliveryModes,
    IS_DELIVERY_MODES_LOADED: state => state.deliveryModesLoaded
};

const mutations = {
    SET_DELIVERY_MODES(state, modes) {
        state.deliveryModes = modes;
        state.deliveryModesLoaded = true;
    }
};

const actions = {
    async LOAD_DELIVERY_MODES({ commit, state }) {
        // Если уже загружены, не загружаем повторно
        if (state.deliveryModesLoaded) {
            return state.deliveryModes;
        }

        try {
            const response = await axios.get('/api/compel/delivery-modes');
            
            if (response.data.success) {
                commit('SET_DELIVERY_MODES', response.data.data);
                return response.data.data;
            }
            
            return [];
        } catch (e) {
            console.error('Error loading Compel delivery modes:', e);
            return [];
        }
    }
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions
};

