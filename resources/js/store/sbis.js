import FileSaver from 'file-saver';

const state = {};
const getters = {};
const mutations = {};
const actions = {
    async ETIKS() {
        try {
            const response = await axios.get(
                'api/invoice/etiks',
                {responseType: 'blob'}
            );
            const file = new Blob(
                [response.data],
                {type: 'application/pdf'});
            const fileURL = URL.createObjectURL(file);
            window.open(fileURL);
        } catch (error) {
            commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
        }
        return true;
    },
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
                .post('api/sbis/clear-gtd', payload)
                .then((response) => {
                    resolve(response);
                })
                .catch((error) => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    reject(error);
                });
        });
    },
    EXPORT({commit}, payload) {
        return new Promise((resolve, reject) => {
            axios
                .post('api/sbis/export', payload)
                .then((response) => {
                    resolve(response);
                })
                .catch((error) => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    reject(error);
                });
        });
    },
    'PACKING-LIST'({commit}, payload) {
        return new Promise((resolve, reject) => {
            axios
                .post('api/sbis/packing-list', payload, { responseType: 'blob' })
                .then((response) => {
                    const file = new Blob(
                        [response.data],
                        {type: 'application/pdf'});
                    const fileURL = URL.createObjectURL(file);
                    window.open(fileURL);
                    resolve(response);
                })
                .catch((error) => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    reject(error);
                });
        });
    },
}

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
}
