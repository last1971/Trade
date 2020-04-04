const state = {
    name: '',
    keyType: Number,
    key: '',
    items: [],
    headers: [],
    aggregateAttributes: [],
};

const getters = {
    NAME: state => state.name,
    KEY: state => state.key,
    KEYTYPE: state => state.keyType,
    URL: state => `/api/${state.name}`,
    ITEM: state => (id) => {
        return _.cloneDeep(_.find(state.items, {[state.key]: id}));
    },
    ITEMS: state => _.cloneDeep(state.items),
    HEADERS: state => state.headers,
};

const mutations = {
    CLEAR(state) {
        state.items = [];
    },

    SET(state, newData) {
        if (!_.isArray(newData)) {
            const error = 'Data must be an array but ' + typeof newData + ' given.';
            this.commit('SNACKBAR/ERROR', error);
            throw new Error(error);
        }
        state.items = _.cloneDeep(newData);
    },

    MERGE(sate, mergeData) {
        if (!_.isArray(mergeData)) {
            const error = 'Data must be an array but ' + typeof newData + ' given.';
            this.commit('SNACKBAR/ERROR', error);
            throw new Error(error);
        }
        _.unionBy(mergeData, state.items, state.key)
    },

    CREATE(state, newDataRow) {
        if (!newDataRow[state.key]) {
            const error = 'New row of ' + state.name + ' must have key';
            this.commit('SNACKBAR/ERROR', error);
            throw new Error(error);
        }
        state.items.push(newDataRow);
    },

    UPDATE(state, newDataRow) {
        let row = _.find(state.items, {[state.key]: newDataRow[state.key]});
        if (row) {
            row = _.cloneDeep(newDataRow);
        } else {
            const error = 'Imposible update ' + state.name + ' with key ' + newDataRow[state.key];
            this.commit('SNACKBAR/ERROR', error);
            throw new Error(error);
        }
    },

    REMOVE(state, removeData) {
        const key = typeof removeData === 'object' ? removeData[state.key] : key;
        if (!key) {
            const error = 'Wrong key value ' + key + ' for ' + state.name;
            this.commit('SNACKBAR/ERROR', error);
            throw new Error(error);
        }
        const index = _.findIndex(state.items, {[state.key]: key});
        if (index < 0) {
            const error = 'Imposible remove ' + state.name + ' with key ' + key;
            this.commit('SNACKBAR/ERROR', error);
            throw new Error(error);
        }
        state.items.splice(index, 1);
    },

};

let actions = {
    GET({getters, commit}, id) {
        return new Promise((resolve, reject) => {
            axios
                .get(getters.URL + '/' + id)
                .then(response => {
                    commit('MERGE', [response.data]);
                    resolve(response.data)
                })
                .catch((error) => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    reject(error);
                });
        });
    },
    ALL({getters, commit}, payload) {
        return new Promise((resolve, reject) => {
            axios
                .get(getters.URL, {params: payload})
                .then((response) => {
                    if (response.data.rows && response.data.data.length > 0) {
                        commit('MERGE', response.data.data);
                    }
                    resolve(response);
                })
                .catch((error) => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    reject(error);
                });
        });
    },
    REMOVE({getters, commit}, id) {
        return new Promise((resolve, reject) => {
            axios.delete(getters.URL + '/' + id)
                .then((response) => {
                    commit(
                        'SNACKBAR/SET',
                        {
                            text: getters.NAME + ' with id ' + id + ' was deleted.',
                            color: 'success',
                            snackbar: true,
                            timeout: 3000
                        },
                        {root: true}
                    );
                    commit('REMOVE', id);
                    resolve(response);
                })
                .catch((error) => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    reject(error);
                });
        });
    },
    CREATE({getters, commit}, payload) {
        const {item} = payload;
        return new Promise((resolve, reject) => {
            axios.post(getters.URL, item)
                .then(response => {
                    commit('CREATE', response.data);
                    commit(
                        'SNACKBAR/SET',
                        {
                            text: getters.NAME + ' with id ' + response.data[getters.KEY] + ' was created.',
                            color: 'success',
                            snackbar: true,
                            timeout: 3000
                        },
                        {root: true}
                    );
                    resolve(response)
                })
                .catch((error) => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    reject(error);
                });
        });
    },
    UPDATE({getters, commit}, payload) {
        const {item} = payload;
        return new Promise((resolve, reject) => {
            axios.put(getters.URL + '/' + item[getters.KEY], item)
                .then(response => {
                    commit('UPDATE', response.data);
                    commit(
                        'SNACKBAR/SET',
                        {
                            text: getters.NAME + ' with id ' + item[getters.KEY] + ' was saved.',
                            color: 'success',
                            snackbar: true,
                            timeout: 3000
                        },
                        {root: true}
                    );
                    resolve(response)
                })
                .catch((error) => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    reject(error);
                });
        });
    },
};

export default {
    state,
    getters,
    mutations,
    actions
}
