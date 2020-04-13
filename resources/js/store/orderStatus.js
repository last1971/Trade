const state = {
    statuses: [
        'Формируется',
        'Сформирован',
        'В Пути',
        'Частично пришел',
        'Пришел',
        'Закрыт',
        'К выполнению'
    ]
};

const getters = {
    ALL: state => state.statuses,
    GET: state => index => state.statuses[index],
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
