const state = {
    snackbar: {
        text: '',
        status: false,
        color: 'info',
        timeout: 30000,
        multi: true,
    },
    queue: [],
    timeout: 30000,
};

const getters = {
    GET: state => state.snackbar
};

const mutations = {
    SET(state, payload) {
        Object.assign(state.snackbar, payload)
    },
    STATUS(state, value) {
        state.snackbar.status = value;
    },
    PUSH(state, payload) {
        state.queue.push(payload);
        if (state.queue.length === 1) Object.assign(state.snackbar, payload);
    },
    ERROR(state, text) {
        const payload = {text, color: 'error', status: true, timeout: state.timeout};
        this.commit('SNACKBAR/PUSH', payload);
    },
    SHIFT(state) {
        state.queue.shift();
        if (state.queue.length > 1) Object.assign(state.snackbar, state.queue[0]);
    },
};

const actions = {};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions
};
