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
};

const getters = {
    AUTH_STATUS: state => state.status,
    IS_GUEST: state => state.roles.indexOf('guest') >= 0,
    IS_LOGGEDIN: state => !!state.token,
    GET: state => state.user,
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
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions
};



