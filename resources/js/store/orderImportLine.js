import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.headers = [
    {
        text: 'Товар',
        value: 'GOODSCODE',
    },
    {
        text: 'Наименование',
        value: 'name',
    },
    {
        text: 'Корпус',
        value: 'case',
    },
    {
        text: 'Производитель',
        value: 'producer',
    },
    {
        text: 'Кол.',
        value: 'quantity',
        align: 'right',
    },
    {
        text: 'Цена',
        value: 'price',
        align: 'right',
    },
    {
        text: 'Сумма',
        value: 'amount',
        align: 'right',
    },
    {
        text: 'Страна',
        value: 'country',
    },
    {
        text: 'ГТД',
        value: 'declaration',
    },
];

let actions = {
    UPLOAD({state, getters, commit, rootGetters}, payload) {
        if (!payload.files) return Promise.reject('File is empty');
        const data = new FormData();
        data.append('file', payload.files[0])
        return new Promise((resolve, reject) => {
            axios.post(getters.URL, data, {headers: {'Content-Type': 'multipart/form-data'}})
                .then(response => {
                    response.data.forEach((orderImportLine) => {
                        if (orderImportLine.good) {
                            commit('GOOD/UPDATE', orderImportLine.good, {root: true});
                        }
                    });
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

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions,
}
