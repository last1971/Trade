const success = (commit, resp) => {
    const token = resp.data.token;
    const user = resp.data.user;
    localStorage.setItem('token', token);
    axios.defaults.headers.common['Authorization'] = 'Bearer ' + token;
    commit('AUTH_SUCCESS', token);
    commit('SET_TOKEN', token);
    commit('SET_USER', user);
    commit('SET_ROLES', resp.data.roles);
    commit('SET_PERMISSIONS', resp.data.permissions)
};

const state = {
    status: '',
    token: localStorage.getItem('token') || document.head.querySelector('meta[name="token"]'),
    user: null,
    roles: localStorage.getItem('roles') ? JSON.parse(localStorage.getItem('roles')) : [],
    permissions: localStorage.getItem('permissions') ? JSON.parse(localStorage.getItem('permissions')) : [],
    options: {},
    localOptions: JSON.parse(localStorage.getItem('options')) || {},
};

const getters = {
    AUTH_STATUS: state => state.status,
    IS_ADMIN: state => state.roles.indexOf('admin') >= 0,
    IS_GUEST: state => state.roles.indexOf('guest') >= 0 && state.roles.length === 1,
    IS_LOGGEDIN: state => !!state.token,
    GET: state => state.user,
    LOCAL_OPTION: state => key => state.localOptions[key],
    PERMISSIONS: state => state.permissions,
    ROLES: state => state.roles,
    HAS_PERMISSION: state => key => {
        const keys = key.split('.');
        for (const permission of state.permissions) {
            const permissions = permission.split('.');
            permissions.length = keys.length;
            const res = permissions.reduce(
                (sum, p, i) => (p === '*' || keys[i] === p) && sum, true
            )
            if (res) return true;
        }
        return false;
    }
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
    CLEAR_LOCAL_OPTION(state) {
        localStorage.removeItem('options');
        state.localOptions = {};

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
        localStorage.setItem('roles', JSON.stringify(roles));
    },
    SET_PERMISSIONS(state, permissions) {
        state.permissions = permissions;
        localStorage.setItem('permissions', JSON.stringify(permissions));
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
    FORGOT({commit}, user) {
        return new Promise((resolve, reject) => {
            commit('AUTH_REQUEST');
            axios.post('/api/forgot', user)
                .then(resp => {
                    commit(
                        'SNACKBAR/SET',
                        {
                            text: resp.data.message,
                            status: true,
                            color: 'info',
                            timeout: 3000,
                            multi: true,
                        },
                        {
                            root: true
                        }
                    );
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
    CHECK({commit}, token) {
        return new Promise((resolve, reject) => {
            commit('AUTH_REQUEST');
            axios.post('/api/check-token', {token})
                .then(resp => {
                    commit('SNACKBAR/SET', {
                        text: 'Вы успели во время',
                        status: true,
                        color: 'info',
                        timeout: 3000,
                    }, {root: true});
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
                    commit('SET_PERMISSIONS', resp.data.permissions)
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
                    commit('CLEAR_LOCAL_OPTION');
                    localStorage.removeItem('token');
                    localStorage.removeItem('roles');
                    localStorage.removeItem('permissions');
                    localStorage.removeItem('headers');
                    localStorage.removeItem('isApiSeller');
                    delete axios.defaults.headers.common['Authorization'];
                    resolve();
                })
                .catch(err => {
                    reject(err);
                });
        })
    },
    RESET({commit}, user) {
        return new Promise((resolve, reject) => {
            commit('AUTH_REQUEST');
            axios.post('/api/reset-password', user)
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



