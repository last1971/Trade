const state = {
    statuses: [
        'Формируется',
        'Сформирован',
        'Зарезервирован',
        'В подборке',
        'Подобран',
        'Закрыт',
        'Корзина'
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
