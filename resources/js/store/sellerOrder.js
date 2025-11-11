import model from './model'
import _ from 'lodash'
import createLocalStorageSync from '../helpers/localStorage';

let state = _.cloneDeep(model.state);

let getters = _.cloneDeep(model.getters);

let mutations = _.cloneDeep(model.mutations);

let actions = _.cloneDeep(model.actions);

state.name = 'seller-order';

state.key = 'id';

state.headers = [];


const activeOrderIdsSync = createLocalStorageSync('seller_order_active_ids');
// ID активных заказов: { sellerId: orderId }
state.activeOrderIds = activeOrderIdsSync.get();

// Кеш строк заказов: { orderId: { lines: [], total: 0, loading: false } }
state.orderLines = {};

// Состояние добавления строки в заказ: { orderId: boolean }
state.addingLine = {};

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

// Получить строки заказа из кеша
getters['GET_ORDER_LINES'] = state => orderId => {
    return state.orderLines[orderId] || { lines: [], total: 0, loading: false };
};

// Проверить, идет ли добавление строки в заказ
getters['IS_ADDING_LINE'] = state => orderId => {
    return state.addingLine[orderId] || false;
};

// Установить активный заказ
mutations['SET_ACTIVE_ID'] = (state, { sellerId, orderId }) => {
    state.activeOrderIds = {
        ...state.activeOrderIds,
        [sellerId]: orderId
    };
    activeOrderIdsSync.set(state.activeOrderIds);
};

// Убрать активный заказ
mutations['REMOVE_ACTIVE_ID'] = (state, sellerId) => {
    const newIds = { ...state.activeOrderIds };
    delete newIds[sellerId];
    state.activeOrderIds = newIds;
    activeOrderIdsSync.set(state.activeOrderIds);
};

// Добавить заказ в список
mutations['ADD_ORDER'] = (state, { sellerId, order }) => {
    // Проверяем, нет ли уже такого заказа
    const exists = state.items.find(o => o.id === order.id);
    if (!exists) {
        state.items.push(order);
    }
};

// Обновить поля заказа
mutations['UPDATE_ORDER_FIELDS'] = (state, { orderId, fields }) => {
    const order = state.items.find(o => o.id === orderId);
    if (order) {
        Object.assign(order, fields);
    }
};

// Установить состояние загрузки строк
mutations['SET_LINES_LOADING'] = (state, { orderId, loading }) => {
    if (!state.orderLines[orderId]) {
        state.orderLines = {
            ...state.orderLines,
            [orderId]: { lines: [], total: 0, loading: false }
        };
    }
    state.orderLines[orderId].loading = loading;
};

// Сохранить строки заказа в кеш
mutations['SET_ORDER_LINES'] = (state, { orderId, lines, total }) => {
    state.orderLines = {
        ...state.orderLines,
        [orderId]: { lines, total, loading: false }
    };
};

// Очистить строки заказа из кеша
mutations['CLEAR_ORDER_LINES'] = (state, orderId) => {
    const newOrderLines = { ...state.orderLines };
    delete newOrderLines[orderId];
    state.orderLines = newOrderLines;
};

// Удалить заказ полностью (из списка)
mutations['REMOVE_ORDER'] = (state, orderId) => {
   const newItems = state.items.filter((item) => item.id !== orderId);
   state.items = newItems;
};

// Установить состояние добавления строки
mutations['SET_ADDING_LINE'] = (state, { orderId, adding }) => {
    state.addingLine = {
        ...state.addingLine,
        [orderId]: adding
    };
};

// Добавить строку в кеш заказа
mutations['ADD_LINE_TO_CACHE'] = (state, { orderId, line }) => {
    if (!state.orderLines[orderId]) {
        state.orderLines = {
            ...state.orderLines,
            [orderId]: { lines: [], total: 0, loading: false }
        };
    }
    state.orderLines[orderId].lines.push(line);
    state.orderLines[orderId].total = state.orderLines[orderId].lines.length;
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
actions['ADD_LINE'] = async ({ state, getters, commit, dispatch }, { sellerId, salesId, line, amountToAdd, lineData }) => {
    commit('SET_ADDING_LINE', { orderId: salesId, adding: true });
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
        
        // Если передали данные строки и операция успешна, добавляем в кеш
        if (lineData && response.data) {
            // Если в ответе есть line_id или другие данные - используем их
            const resultLine = { ...lineData };
            
            // Если API вернул данные о добавленной строке, обновляем
            if (response.data.line_id) {
                resultLine.line_id = response.data.line_id;
            }
            if (response.data.lines && response.data.lines.length > 0) {
                // Если вернулся массив строк, берём первую
                const apiLine = response.data.lines[0];
                if (apiLine.line_id) resultLine.line_id = apiLine.line_id;
            }
            
            commit('ADD_LINE_TO_CACHE', { orderId: salesId, line: resultLine });
        }
        
        return response.data;
    } catch (error) {
        commit('SNACKBAR/ERROR', error.response?.data?.message || 'Ошибка добавления строки', { root: true });
        throw error;
    } finally {
        commit('SET_ADDING_LINE', { orderId: salesId, adding: false });
    }
};

// ПОЛУЧЕНИЕ СТРОК ЗАКАЗА (с кешированием)
actions['GET_LINES'] = async ({ state, getters, commit }, { salesId, sellerId, forceReload = false }) => {
    // Если не принудительная перезагрузка и есть кеш - возвращаем из кеша
    if (!forceReload && state.orderLines[salesId] && state.orderLines[salesId].lines.length > 0) {
        return state.orderLines[salesId];
    }
    
    commit('SET_LINES_LOADING', { orderId: salesId, loading: true });
    
    try {
        const response = await axios.get(`${getters.URL}/${salesId}/lines`, {
            params: { seller_id: sellerId }
        });
        
        const data = response.data;
        
        // Сохраняем в кеш
        commit('SET_ORDER_LINES', {
            orderId: salesId,
            lines: data.lines || [],
            total: data.total || 0
        });
        
        return data;
    } catch (error) {
        commit('SET_LINES_LOADING', { orderId: salesId, loading: false });
        commit('SNACKBAR/ERROR', error.response?.data?.message || 'Ошибка загрузки строк заказа', { root: true });
        throw error;
    }
};

// ПРЕДЗАГРУЗКА СТРОК (фоновая, без ошибок в UI)
actions['PRELOAD_LINES'] = async ({ dispatch }, { salesId, sellerId }) => {
    try {
        await dispatch('GET_LINES', { salesId, sellerId, forceReload: false });
    } catch (error) {
        // Тихо игнорируем ошибки предзагрузки
        console.warn('Preload lines failed:', error);
    }
};

// ИЗМЕНЕНИЕ КОЛИЧЕСТВА В СТРОКЕ
actions['UPDATE_LINE_QUANTITY'] = async ({ state, getters, commit }, { salesId, sellerId, lineId, quantity, amountDiff }) => {
    try {
        const response = await axios.put(`${getters.URL}/${salesId}/lines`, {
            line_id: lineId != null ? String(lineId) : lineId,
            quantity: quantity,
            seller_id: sellerId
        });
        
        // Обновляем количество в кеше
        if (state.orderLines[salesId]) {
            const line = state.orderLines[salesId].lines.find(l => l.line_id === lineId);
            if (line) {
                line.sales_qty = quantity;
                // Пересчитываем amount для строки
                line.amount = line.price * quantity;
            }
        }
        
        // Обновляем сумму заказа
        const order = state.items.find(o => o.id === salesId);
        if (order && amountDiff !== undefined) {
            order.amount = (parseFloat(order.amount) || 0) + parseFloat(amountDiff);
        }
        
        return response.data;
    } catch (error) {
        commit('SNACKBAR/ERROR', error.response?.data?.message || 'Ошибка изменения количества', { root: true });
        throw error;
    }
};

// УДАЛЕНИЕ СТРОКИ
actions['DELETE_LINE'] = async ({ state, getters, commit }, { salesId, sellerId, lineId, amountToSubtract }) => {
    try {
        const response = await axios.delete(`${getters.URL}/${salesId}/lines`, {
            params: {
                line_id: lineId != null ? String(lineId) : lineId,
                seller_id: sellerId
            }
        });
        
        // Удаляем строку из кеша
        if (state.orderLines[salesId]) {
            const index = state.orderLines[salesId].lines.findIndex(l => l.line_id === lineId);
            if (index !== -1) {
                state.orderLines[salesId].lines.splice(index, 1);
                state.orderLines[salesId].total = state.orderLines[salesId].lines.length;
            }
        }
        
        // Обновляем сумму заказа
        const order = state.items.find(o => o.id === salesId);
        if (order && amountToSubtract !== undefined) {
            order.amount = Math.max(0, (parseFloat(order.amount) || 0) - parseFloat(amountToSubtract));
        }
        
        return response.data;
    } catch (error) {
        commit('SNACKBAR/ERROR', error.response?.data?.message || 'Ошибка удаления строки', { root: true });
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
