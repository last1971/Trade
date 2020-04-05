const success = (commit, resp) => {
    const token = resp.data.token;
    const user = resp.data.user;
    localStorage.setItem('token', token);
    axios.defaults.headers.common['Authorization'] = 'Bearer ' + token;
    commit('AUTH_SUCCESS', token);
    commit('SET_TOKEN', token);
    commit('SET_USER', user);
    commit('SET_ROLES', resp.data.roles);
};

const state = {
    status: '',
    token: localStorage.getItem('token') || document.head.querySelector('meta[name="token"]'),
    user: null,
    roles: [],
    options: {},
    localOptions: JSON.parse(localStorage.getItem('options')) || {},
};

const getters = {
    AUTH_STATUS: state => state.status,
    IS_GUEST: state => state.roles.indexOf('guest') >= 0 && state.roles.length === 1,
    IS_LOGGEDIN: state => !!state.token,
    GET: state => state.user,
    LOCAL_OPTION: state => key => state.localOptions[key],
};

const mutations = {
    AUTH_REQUEST(state) {
        state.status = 'loading';
    },
    AUTH_SUCCESS(state, token) {
        state.status = 'success';
        state.token = token;
    },
    AUTH_ERROR(state) {
        state.status = 'error';
    },
    LOGOUT(state) {
        state.status = '';
        state.token = '';
        state.user = null;
    },
    SET_TOKEN(state, token) {
        state.token = token
    },
    SET_USER(state, user) {
        state.user = user;
    },
    SET_ROLES(state, roles) {
        state.roles = roles;
    },
    SET_OPTION(state, option) {
        Object.assign(state.options, option)
    },
    SET_LOCAL_OPTION(state, option) {
        Object.assign(state.localOptions, option);
        localStorage.setItem('options', JSON.stringify(state.localOptions));
    }
};

const actions = {
    LOGIN({commit}, user) {
        return new Promise((resolve, reject) => {
            commit('AUTH_REQUEST');
            axios.post('/api/login', user)
                .then(resp => {
                    success(commit, resp);
                    resolve(resp);
                })
                .catch(err => {
                    commit('AUTH_ERROR');
                    localStorage.removeItem('token');
                    commit('SNACKBAR/ERROR', err.response.data.message, {root: true});
                    reject(err);
                })
        })
    },
    REFRESH({commit}) {
        return new Promise((resolve, reject) => {
            axios.get('/api/refresh-user')
                .then(resp => {
                    commit('SET_USER', resp.data.user);
                    commit('SET_ROLES', resp.data.roles);
                    resolve(resp);
                })
                .catch(err => {
                    reject(err);
                })
        })
    },
    LOGOUT({commit}) {
        return new Promise((resolve, reject) => {
            axios.get('/api/logout')
                .then(() => {
                    commit('LOGOUT');
                    localStorage.removeItem('token');
                    delete axios.defaults.headers.common['Authorization'];
                    resolve();
                })
                .catch(err => {
                    reject(err);
                });
        })
    },
    REGISTER({commit}, user) {
        return new Promise((resolve, reject) => {
            commit('AUTH_REQUEST');
            axios.post('/api/register', user)
                .then(resp => {
                    success(commit, resp);
                    resolve(resp);
                })
                .catch(err => {
                    commit('AUTH_ERROR');
                    localStorage.removeItem('token');
                    reject(err);
                })
        })
    },
    OPTION({state, commit}, payload) {
        return new Promise((resolve, reject) => {
            const option = state.options[payload.option];
            if (option && _.isEqual(option, payload.option)) return resolve(option);
            axios.put('/api/user-option/' + payload.option, payload.value)
                .then((response) => {
                    commit('SET_OPTION', {[payload.option]: payload.value});
                    resolve(response.data);
                })
                .catch(err => {
                    reject(err);
                })
        })
    },
    OPTIONS({state, commit}, option) {
        return new Promise((resolve, reject) => {
            axios.get('/api/user-option/' + option)
                .then((response) => {
                    commit('SET_OPTION', {[option]: response.data});
                    resolve(response.data);
                })
                .catch(err => {
                    reject(err);
                })
        })
    }
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions
};



