import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.fillable = ['PRICEROZN', 'PRICEMOPT', 'PRICEOPT', 'QUANMOPT', 'QUANOPT', 'DOLLAR', 'GOODSCODE'];

state.name = 'retail-price';

state.key = 'PRICECODE';

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
