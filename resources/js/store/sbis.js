import FileSaver from 'file-saver';

const state = {};
const getters = {};
const mutations = {};
const actions = {
    XLSX({commit}, payload) {
        return new Promise((resolve, reject) => {
            axios
                .post('api/sbis/xlsx', payload, {responseType: 'blob'})
                .then((response) => {
                    FileSaver.saveAs(response.data, 'отгрузка' + _.now() + '.xlsx');
                    resolve(response);
                })
                .catch((error) => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    reject(error);
                });
        });
    },
    'CLEAR-GTD'({commit}, payload) {
        return new Promise((resolve, reject) => {
            axios
                .post('api/sbis/clear-gtd', payload, {responseType: 'blob'})
                .then((response) => {
                    resolve(response);
                })
                .catch((error) => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    reject(error);
                });
        });
    }
}

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
}
