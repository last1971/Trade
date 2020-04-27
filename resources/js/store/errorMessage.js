const state = {
    messages: {
        'validation.string': 'введите строку',
        'validation.required': 'обязателен',
        'validation.unique': 'не уникален',
        'validation.integer': 'не целое число',
    }
};

const getters = {
    ALL: state => state.messages,
    GET: state => index => state.messages[index],
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
