import model from './model'
import FileSaver from 'file-saver';
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'certificate';

state.key = 'id';

state.fillable = ['number', 'type', 'name', 'date_from', 'date_to', 'remark'];

state.headers = [
    {
        text: 'Номер',
        value: 'number',
    },
    {
        text: 'Тип',
        value: 'type',
    },
    {
        text: 'Название',
        value: 'name',
    },
    {
        text: 'Действует с',
        value: 'date_from',
    },
    {
        text: 'Действует по',
        value: 'date_to',
    },
    {
        text: 'Площадки',
        value: 'marketplaces',
        sortable: false,
    },
    {
        text: 'Примечание',
        value: 'remark',
    },
    {
        text: 'Файл',
        value: 'actions',
        sortable: false,
    },
];

let actions = _.assign({}, model.actions, {
    UPLOAD({getters, commit}, payload) {
        if (!payload.file) return Promise.reject('File is empty');
        const data = new FormData();
        data.append('file', payload.file);
        ['number', 'type', 'name', 'date_from', 'date_to', 'remark'].forEach((attribute) => {
            if (payload[attribute]) data.append(attribute, payload[attribute]);
        });
        return new Promise((resolve, reject) => {
            axios.post(getters.URL, data, {headers: {'Content-Type': 'multipart/form-data'}})
                .then(response => {
                    commit('CREATE', response.data);
                    resolve(response);
                })
                .catch((error) => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    reject(error);
                });
        });
    },

    DOWNLOAD({getters, commit}, payload) {
        return new Promise((resolve, reject) => {
            axios.get(getters.URL + '/' + payload.id + '/download', {responseType: 'blob'})
                .then(response => {
                    FileSaver.saveAs(response.data, payload.original_name);
                    resolve(response);
                })
                .catch((error) => {
                    commit('SNACKBAR/ERROR', 'Не удалось скачать файл', {root: true});
                    reject(error);
                });
        });
    },

    OPEN({getters, commit}, payload) {
        return new Promise((resolve, reject) => {
            axios.get(getters.URL + '/' + payload.id + '/download', {responseType: 'blob'})
                .then(response => {
                    const file = new Blob([response.data], {type: payload.mime});
                    window.open(URL.createObjectURL(file));
                    resolve(response);
                })
                .catch((error) => {
                    commit('SNACKBAR/ERROR', 'Не удалось открыть файл', {root: true});
                    reject(error);
                });
        });
    },

    'ATTACH-GOODS'({getters, commit}, payload) {
        return new Promise((resolve, reject) => {
            axios.post(getters.URL + '/' + payload.id + '/goods', {good_ids: payload.good_ids})
                .then(response => {
                    commit('UPDATE', response.data);
                    resolve(response);
                })
                .catch((error) => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    reject(error);
                });
        });
    },

    'DETACH-GOOD'({getters, commit}, payload) {
        return new Promise((resolve, reject) => {
            axios.delete(getters.URL + '/' + payload.id + '/goods/' + payload.good_id)
                .then(response => {
                    resolve(response);
                })
                .catch((error) => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    reject(error);
                });
        });
    },

    MARKETPLACES({commit}) {
        return new Promise((resolve, reject) => {
            axios.get('/api/certificate-marketplaces')
                .then(response => {
                    resolve(response.data);
                })
                .catch((error) => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    reject(error);
                });
        });
    },

    TYPES({commit}) {
        return new Promise((resolve, reject) => {
            axios.get('/api/certificate-types')
                .then(response => {
                    resolve(response.data);
                })
                .catch((error) => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    reject(error);
                });
        });
    },

    'MARK-MARKETPLACE'({getters, commit}, payload) {
        return new Promise((resolve, reject) => {
            axios.post(getters.URL + '/' + payload.id + '/marketplaces', {
                name: payload.name,
                uploaded_at: payload.uploaded_at,
            })
                .then(response => {
                    commit('UPDATE', response.data);
                    resolve(response);
                })
                .catch((error) => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    reject(error);
                });
        });
    },

    'UNMARK-MARKETPLACE'({getters, commit}, payload) {
        return new Promise((resolve, reject) => {
            axios.delete(getters.URL + '/' + payload.id + '/marketplaces/' + payload.marketplace_id)
                .then(response => {
                    commit('UPDATE', response.data);
                    resolve(response);
                })
                .catch((error) => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    reject(error);
                });
        });
    },
});

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions,
}
