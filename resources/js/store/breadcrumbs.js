const state = {
    items: [],
};

const getters = {
    ALL: state => state.items,
};

const mutations = {
    // Страница-список задаёт свою цепочку целиком: [{text, to, exact?}, ...].
    // disabled не хранится — последняя крошка гасится при рендере (App.vue).
    SET(state, items) {
        state.items = items;
    },
    // Карточка добавляет себя в конец цепочки; если крошка этого маршрута
    // уже есть (переход карточка→карточка) — заменяет её и обрезает хвост.
    PUT(state, item) {
        const index = state.items.findIndex(i => i.to && i.to.name === item.to.name);
        if (index >= 0) {
            state.items.splice(index, state.items.length - index, item);
        } else {
            state.items.push(item);
        }
    },
    // Уходя со страницы, запоминаем её точный адрес (params+query):
    // возврат по крошке ведёт туда, откуда пришли, с фильтрами и страницей.
    SYNC(state, location) {
        const item = state.items.find(i => i.to && i.to.name === location.name);
        if (item) {
            item.to = {name: location.name, params: location.params, query: location.query};
        }
    },
};

const actions = {};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions
};
