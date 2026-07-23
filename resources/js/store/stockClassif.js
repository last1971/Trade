import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'stock-classif';

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: {
        ...model.actions,
        // Страница списка «Разгребание склада» из снапшота.
        LIST({getters, commit}, payload) {
            return axios.get(getters.URL, {params: payload})
                .then(response => response.data)
                .catch(error => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    throw error;
                });
        },
        // Статус фонового пересчёта (running + время данных).
        STATUS({getters}) {
            return axios.get(getters.URL + '/status')
                .then(response => response.data);
        },
        // Категории склада (с подкатегориями) для фильтра — фронт тянет один раз.
        CATEGORIES({getters}) {
            return axios.get(getters.URL + '/categories')
                .then(response => response.data);
        },
        // Запуск пересчёта снапшота в фоне (~2.5 мин).
        REFRESH({getters, commit}) {
            return axios.post(getters.URL + '/refresh')
                .then(response => response.data)
                .catch(error => {
                    commit('SNACKBAR/ERROR', error.response.data.message, {root: true});
                    throw error;
                });
        },
    },
}
