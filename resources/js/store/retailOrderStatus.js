import _ from 'lodash';

const state = {
    statuses: {
        'Формируется': 0,
        'Резерв': 1,
        'Заказан': 2,
        'Корзина': 4,
        'Передан': 6,
    }
};

const getters = {
    ALL: state => state.statuses,
    'GET-CODE': state => index => state.statuses[index] || -1,
    'GET-STATUS': state => index => _.invert(state.statuses)[index] || 'артефакт',
};

const mutations = {};

const actions = {};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions
};
