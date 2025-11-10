const state = {
    deliveryModes: [        
        {
            id: '5641730329',
            delivery_mode: 'РФ DPD Economy',
            warehouse_area: 'CCV0030',
            shipping_schedule: 'Пятница',
            payment_terms: 'Доставка в отдельном заказе',
            address: '344002, Россия, Ростовская обл, г Ростов-на-Дону, Крыловской пер, д 10/13, кв 55'
        },
        {
            id: '5646674832',
            delivery_mode: 'РФ DPD Economy',
            warehouse_area: 'CCV0030',
            shipping_schedule: 'Пятница',
            payment_terms: 'Доставка в отдельном заказе',
            address: '634050, Томская обл, г Томск, ул Розы Люксембург, д 19'
        },
        {
            id: '5643140578',
            delivery_mode: 'РФ DPD Express',
            warehouse_area: 'CCV0030',
            shipping_schedule: 'Пятница',
            payment_terms: 'Доставка в отдельном заказе',
            address: '344002, Россия, Ростовская обл, г Ростов-на-Дону, Крыловской пер, д 10/13, кв 55'
        },
        {
            id: '5646674831',
            delivery_mode: 'РФ DPD Express',
            warehouse_area: 'CCV0030',
            shipping_schedule: 'Пятница',
            payment_terms: 'Доставка в отдельном заказе',
            address: '634050, Томская обл, г Томск, ул Розы Люксембург, д 19'
        },
        {
            id: '5646674833',
            delivery_mode: 'РФ DPD OPTIMUM',
            warehouse_area: 'CCV0030',
            shipping_schedule: 'Пятница',
            payment_terms: 'Доставка в отдельном заказе',
            address: '634050, Томская обл, г Томск, ул Розы Люксембург, д 19'
        }
    ],
    deliveryModesLoaded: true
};

const getters = {
    GET_DELIVERY_MODES: state => state.deliveryModes,
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

