const state = {
    snackbar: {
        text: '',
        status: false,
        color: 'info',
        timeout: 10000,
        multi: true,
    },
    queue: [],
    timeout: 10000,
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
        Object.assign(state.snackbar, payload)
        /*state.queue.push(payload);
        if (state.queue.length === 1) Object.assign(state.snackbar, payload);
        setTimeout(() => { console.log(1); this.commit('SNACKBAR/SHIFT'); }, state.timeout )
        */
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
