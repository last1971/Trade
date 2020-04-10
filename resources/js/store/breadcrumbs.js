const state = {
    items: [],
};

const getters = {
    ALL: state => state.items,
};

const mutations = {
    SET(state, items) {
        state.items = items;
    },
    PUT(state, item) {
        const index = _.findIndex(state.items, {text: item.text});
        if (index >= 0) {
            state.length = index + 1;
        } else {
            state.items.push(item)
        }
        const length = state.items.length;
        state.items[length - 1].disabled = true;
        if (length > 1) state.items[length - 2].disabled = false;
    }
};

const actions = {};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions
};
