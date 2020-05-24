import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);
let actions = {
    UPLOAD({state, getters, commit, rootGetters}, payload) {
        if (!payload.files) return Promise.reject('File is empty');
        const data = new FormData();
        data.append('file', payload.files[0])
        return new Promise((resolve, reject) => {
            axios.post(getters.URL, data, {headers: {'Content-Type': 'multipart/form-data'}})
                .then(response => {
                    resolve(response);
                })
                .catch((error) => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    reject(error);
                });
        });
    }
}

state.name = 'order-import-line';

state.key = 'index';

state.headers = [
    // {text: '', value: 'actions', width: 10, sortable: false},

];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions,
}
