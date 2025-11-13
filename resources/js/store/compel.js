const state = {
    deliveryModes: [
        {
            id: '5641730329',
            delivery_mode: 'РФ DPD Economy',
            warehouse_area: 'CCV0030',
            shipping_schedule: 'Пятница',
            payment_terms: 'Доставка в отдельном заказе',
            address: '344002, Россия, Ростовская обл, г Ростов-на-Дону, Крыловской пер, д 10/13, кв 55',
            firm_id: 38
        },
        {
            id: '5646674832',
            delivery_mode: 'РФ DPD Economy',
            warehouse_area: 'CCV0030',
            shipping_schedule: 'Пятница',
            payment_terms: 'Доставка в отдельном заказе',
            address: '634050, Томская обл, г Томск, ул Розы Люксембург, д 19',
            firm_id: 38
        },
        {
            id: '5643140578',
            delivery_mode: 'РФ DPD Express',
            warehouse_area: 'CCV0030',
            shipping_schedule: 'Пятница',
            payment_terms: 'Доставка в отдельном заказе',
            address: '344002, Россия, Ростовская обл, г Ростов-на-Дону, Крыловской пер, д 10/13, кв 55',
            firm_id: 38
        },
        {
            id: '5646674831',
            delivery_mode: 'РФ DPD Express',
            warehouse_area: 'CCV0030',
            shipping_schedule: 'Пятница',
            payment_terms: 'Доставка в отдельном заказе',
            address: '634050, Томская обл, г Томск, ул Розы Люксембург, д 19',
            firm_id: 38
        },
        {
            id: '5646674833',
            delivery_mode: 'РФ DPD OPTIMUM',
            warehouse_area: 'CCV0030',
            shipping_schedule: 'Пятница',
            payment_terms: 'Доставка в отдельном заказе',
            address: '634050, Томская обл, г Томск, ул Розы Люксембург, д 19',
            firm_id: 38
        },
        {
            id: '5640721079',
            delivery_mode: 'РФ DPD Economy',
            warehouse_area: 'CCV0030',
            shipping_schedule: 'Пятница',
            payment_terms: 'Доставка в отдельном заказе',
            address: '634009, Томская обл, г Томск, пр-кт Ленина, д 159',
            firm_id: 31
        },
        {
            id: '5645224576',
            delivery_mode: 'РФ DPD Express',
            warehouse_area: 'CCV0030',
            shipping_schedule: 'Пятница',
            payment_terms: 'Доставка в отдельном заказе',
            address: '634009, Томская обл, г Томск, пр-кт Ленина, д 159',
            firm_id: 31
        }
    ],
    deliveryModesLoaded: true
};

const getters = {
    GET_DELIVERY_MODES: (state, _getters, _rootState, rootGetters) => {
        const user = rootGetters['AUTH/GET'];

        // Если пользователь не загружен или нет фирм, возвращаем способы для firm_id: 38
        if (!user || !user.user_firms || !Array.isArray(user.user_firms) || user.user_firms.length === 0) {
            return state.deliveryModes.filter(mode => mode.firm_id === 38);
        }

        // Получаем ID фирм пользователя
        const userFirmIds = user.user_firms.map(uf => uf.firm?.FIRM_ID || uf.firm?.firm_id).filter(id => id);

        // Возвращаем способы доставки только для фирм пользователя
        return state.deliveryModes.filter(mode => userFirmIds.includes(mode.firm_id));
    },
    IS_DELIVERY_MODES_LOADED: state => state.deliveryModesLoaded
};

const mutations = {
    SET_DELIVERY_MODES(state, modes) {
        state.deliveryModes = modes;
        state.deliveryModesLoaded = true;
    }
};

const actions = {};

export default {
    namespaced: true,
    state,
    getters,
    mutations,
    actions
};

