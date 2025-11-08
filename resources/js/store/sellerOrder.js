import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

let getters = _.cloneDeep(model.getters);

let mutations = _.cloneDeep(model.mutations);

let actions = _.cloneDeep(model.actions);

state.name = 'seller-order';

state.key = 'id';

state.headers = [];

// ID активных заказов: { sellerId: orderId }
state.activeOrderIds = {};

// Получить заказы для поставщика
getters['GET_BY_SELLER'] = state => sellerId => {
    return state.items.filter(order => order.seller_id === sellerId);
};

// Получить ID активного заказа
getters['GET_ACTIVE_ID'] = state => sellerId => {
    return state.activeOrderIds[sellerId] || null;
};

// Получить активный заказ
getters['GET_ACTIVE_ORDER'] = state => sellerId => {
    const orderId = state.activeOrderIds[sellerId];
    if (!orderId) return null;
    return state.items.find(order => order.id === orderId) || null;
};

// Установить активный заказ
mutations['SET_ACTIVE_ID'] = (state, { sellerId, orderId }) => {
    state.activeOrderIds = {
        ...state.activeOrderIds,
        [sellerId]: orderId
    };
};

// Убрать активный заказ
mutations['REMOVE_ACTIVE_ID'] = (state, sellerId) => {
    const newIds = { ...state.activeOrderIds };
    delete newIds[sellerId];
    state.activeOrderIds = newIds;
};

// Добавить заказ в список
mutations['ADD_ORDER'] = (state, { sellerId, order }) => {
    // Проверяем, нет ли уже такого заказа
    const exists = state.items.find(o => o.id === order.id);
    if (!exists) {
        state.items.push(order);
    }
};

// СИНХРОНИЗАЦИЯ конкретного поставщика
actions['SYNC_SELLER'] = async ({ state, getters, commit }, sellerId) => {
    try {
        // Загружаем заказы этого поставщика
        const response = await axios.get(getters.URL, {
            params: {
                filterAttributes: ['seller_id'],
                filterOperators: ['='],
                filterValues: [sellerId]
            }
        });
        
        const newOrders = response.data.data || [];
        
        // Удаляем старые заказы этого поставщика из state.items
        const otherOrders = state.items.filter(order => order.seller_id !== sellerId);
        
        // Заменяем: старые заказы других поставщиков + новые заказы этого поставщика
        commit('SET', [...otherOrders, ...newOrders]);
        
        // Проверяем активный заказ - если удалился, убираем
        const activeId = state.activeOrderIds[sellerId];
        if (activeId) {
            const orderExists = newOrders.find(o => o.id === activeId);
            if (!orderExists) {
                commit('REMOVE_ACTIVE_ID', sellerId);
            }
        }
        
        return newOrders;
    } catch (error) {
        commit('SNACKBAR/ERROR', error.response?.data?.message || 'Ошибка синхронизации', { root: true });
        throw error;
    }
};

// ДОБАВЛЕНИЕ СТРОКИ В ЗАКАЗ
actions['ADD_LINE'] = async ({ state, getters, commit }, { sellerId, salesId, line, amountToAdd }) => {
    try {
        const response = await axios.post(`${getters.URL}/${salesId}/lines`, {
            line: line
        });
        
        // Обновляем сумму заказа в сторе
        const order = state.items.find(o => o.id === salesId);
        if (order && amountToAdd) {
            // Прибавляем сумму добавленной позиции к общей сумме заказа
            order.amount = (parseFloat(order.amount) || 0) + parseFloat(amountToAdd);
        }
        
        return response.data;
    } catch (error) {
        commit('SNACKBAR/ERROR', error.response?.data?.message || 'Ошибка добавления строки', { root: true });
        throw error;
    }
};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions,
}
