import FileSaver from 'file-saver';

function queryClear(query) {
    if (query.filterAttributes) {
        const filtreAttributes = [];
        const filterOperators = [];
        query.filterValues = query.filterValues.filter((v, i) => {
            const res = !_.isEmpty(v) || _.isNumber(v);
            if (res) {
                filtreAttributes.push(query.filterAttributes[i]);
                filterOperators.push(query.filterOperators[i]);
            }
            return res;
        });
        query.filterAttributes = filtreAttributes;
        query.filterOperators = filterOperators;
    }
}

const state = {
    name: '',
    keyType: Number,
    key: '',
    items: [],
    headers: [],
    aggregateAttributes: [],
    fillable: [],
    dependentModels: {},
    parentModel: {},
    links: new Map(),
};

const getters = {
    NAME: state => state.name,
    KEY: state => state.key,
    KEYTYPE: state => state.keyType,
    URL: state => `/api/${state.name}`,
    GET: state => (id) => {
        return _.isArray(id)
            ? state.items.filter(
                (item) => id.indexOf(state.keyType === Number ? parseInt(item[state.key]) : item[state.key]) >= 0
            )
            : _.find(state.items, {[state.key]: state.keyType === Number ? parseInt(id) : id})
    },
    ALL: state => state.items,
    HEADERS: state => state.headers,
    FILLABLE: state => state.fillable,
    GET_LINK: state => id => {
        const key = state.keyType === Number ? parseInt(id) : id;
        if (state.links.has(key)) return state.links.get(key);
        return {previous: {name: state.name + 's'}, next: {name: state.name + 's'}}
    }
};

const mutations = {
    SET_LINK(state, payload) {
        state.links.set(payload.key, payload.link);
    },

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

    MERGE(state, mergeData) {
        if (!_.isArray(mergeData)) {
            const error = 'Data must be an array but ' + typeof newData + ' given.';
            this.commit('SNACKBAR/ERROR', error);
            throw new Error(error);
        }
        state.items = _.unionBy(mergeData, state.items, state.key);
    },

    CREATE(state, newDataRow) {
        if (!newDataRow[state.key]) {
            const error = 'New row of ' + state.name + ' must have key';
            this.commit('SNACKBAR/ERROR', error);
            throw new Error(error);
        }
        state.items.push(_.cloneDeep(newDataRow));
    },

    UPDATE(state, newDataRow) {
        let index = _.findIndex(state.items, {[state.key]: newDataRow[state.key]});
        if (index >= 0) {
            state.items.splice(index, 1, _.cloneDeep(newDataRow));
        } else if (!newDataRow[state.key]) {
            const error = 'New row of ' + state.name + ' must have key';
            this.commit('SNACKBAR/ERROR', error);
            throw new Error(error);
        } else {
            state.items.push(_.cloneDeep(newDataRow));
        }
    },

    REMOVE(state, removeData) {
        const key = typeof removeData === 'object' ? removeData[state.key] : removeData;
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
    'TOGGLE-HEADER'(state, value) {
        const index = _.findIndex(state.headers, { value });
        const proxy = _.cloneDeep(state.headers[index]);
        proxy.hidden = !proxy.hidden;
        state.headers.splice(index, 1, proxy);
        const headers = JSON.parse(localStorage.getItem('headers')) || {};
        headers[state.name] = state.headers;
        localStorage.setItem('headers', JSON.stringify(headers));
    }

};

let actions = {
    CACHE({getters, commit, dispatch}, payload) {
        let id = typeof payload === 'object' ? payload.id : payload;
        const res = getters.GET(id);
        if (res) return Promise.resolve(res);
        return dispatch('GET', payload);

    },
    GET({state, getters, commit}, payload) {
        const id = typeof payload === 'object' ? payload.id : payload;
        const query = typeof payload === 'object' ? payload.query : {};
        queryClear(query);
        return new Promise((resolve, reject) => {
            axios
                .get(getters.URL + '/' + id, {params: query})
                .then(response => {
                    commit('MERGE', [response.data.data || response.data]);
                    resolve(response.data.data || response.data)
                })
                .catch((error) => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    reject(error);
                });
        });
    },
    PDF({getters, commit}, payload) {
        const id = typeof payload === 'object' ? payload.id : payload;
        const query = typeof payload === 'object' ? payload.query : {};
        queryClear(query);
        return new Promise((resolve, reject) => {
            if (getters.NAME === 'transfer-out') {
                // Для transfer-out получаем токен и открываем прямую ссылку
                axios.get(getters.URL + '/pdf-token/' + id)
                    .then(response => {
                        const token = response.data.token;
                        const params = new URLSearchParams({...query, token}).toString();
                        const url = `/pdf/transfer-out/${id}?${params}`;
                        window.open(url, '_blank');
                        resolve(response);
                    })
                    .catch(reject);
            } else {
                axios
                    .get(getters.URL + '/export/' + id, {params: query, responseType: 'blob'})
                    .then(response => {
                        FileSaver.saveAs(response.data, getters.PDF(id));
                        resolve(response);
                    })
                    .catch((error) => {
                        const blob = new Blob([error.response.data], {type: 'application/json'});
                        blob.text().then((res) => {
                            commit('SNACKBAR/ERROR', JSON.parse(res).message, {root: true});
                        })
                        reject(error);
                    });
            }
        });
    },
    XML({getters, commit}, payload) {
        const id = typeof payload === 'object' ? payload.id : payload;
        const query = typeof payload === 'object' ? payload.query : {};
        queryClear(query);
        return new Promise((resolve, reject) => {
            axios
                .get(getters.URL + '/xml/' + id, {params: query, responseType: 'blob'})
                .then(response => {
                    FileSaver.saveAs(
                        response.data,
                        response.request.getResponseHeader('Content-Disposition').split('=')[1] + '.xml',
                    );
                    resolve(response);
                })
                .catch((error) => {
                    const blob = new Blob([error.response.data], {type: 'application/json'});
                    blob.text().then((res) => {
                        commit('SNACKBAR/ERROR', JSON.parse(res).message, {root: true});
                    })
                    reject(error);
                });
        });
    },
    async RECEIPT({getters, commit}, payload) {
        try {
            const response = await axios.get(
                getters.URL + '/receipt/' + payload,
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
    SAVE({state, getters, commit}, payload) {
        return new Promise((resolve, reject) => {
            const query = _.cloneDeep(payload);
            queryClear(query);
            axios
                .get(getters.URL + '/export', {params: query, responseType: 'blob'})
                .then((response) => {
                    FileSaver.saveAs(response.data, state.name + '.xlsx');
                    resolve(response);
                })
                .catch((error) => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    reject(error);
                });
        });
    },
    async ALL({state, getters, commit}, payload) {
        try {
            const query = _.cloneDeep(payload);
            queryClear(query);
            const response = await axios.get(getters.URL, {params: query});
            if (response.data.data && response.data.data.length > 0) {
                commit('MERGE', response.data.data);
            }
            return {
                itemIds: response.data.data.map((item) => item[state.key]),
                copyItems: response.data.data,
                total: response.data.total !== undefined ? response.data.total : response.data.meta.total
            }
        } catch (error) {
            commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
            throw error;
        }
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
                            status: true,
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
    CREATE({state, getters, commit, rootGetters}, payload) {
        const create = _.cloneDeep(payload);
        create.item = _.pick(create.item, getters.FILLABLE);
        create.options = _.pick(create.options, ['with', 'aggregateAttributes']);
        return new Promise((resolve, reject) => {
            axios.post(getters.URL, create)
                .then(response => {
                    commit('CREATE', response.data);
                    _.forEach(state.dependentModels, (value, key) => {
                        const model = rootGetters[key + '/GET'](response.data[rootGetters[key + '/KEY']]);
                        if (model) {
                            model[value] = response.data;
                            commit(key + '/UPDATE', model, {root: true});
                        }
                    });
                    commit(
                        'SNACKBAR/SET',
                        {
                            text: getters.NAME + ' with id ' + response.data[getters.KEY] + ' was created.',
                            color: 'success',
                            status: true,
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
    UPDATE({state, getters, commit, rootGetters}, payload) {
        const update = _.cloneDeep(payload);
        update.item = _.pick(update.item, getters.FILLABLE);
        update.options = _.pick(update.options, ['with', 'aggregateAttributes']);
        return new Promise((resolve, reject) => {
            axios.put(getters.URL + '/' + payload.item[getters.KEY], update)
                .then(response => {
                    commit('UPDATE', response.data);
                    /* нужно переосмыслить */
                    /* берем модели внутри котрых есть эта */
                    _.forEach(state.dependentModels, (value, key) => {
                        const models = _.filter(rootGetters[key + '/ALL'], function (model) {
                            return model[value] && model[value][getters.KEY] === response.data[getters.KEY]
                        })
                        /* и меняем ту что внутри взятых моделей на эту  */
                        models.forEach((model) => {
                            model[value] = response.data;
                            commit(key + '/UPDATE', model, {root: true});
                        })
                    });
                    /*   commit(
                           'SNACKBAR/SET',
                           {
                               text: getters.NAME + ' with id ' + payload.item[getters.KEY] + ' was saved.',
                               color: 'success',
                               status: true,
                               timeout: 3000
                           },
                           {root: true}
                       );*/
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
