import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'advanced-buyer';

state.fillable = ['edo_id', 'buyer_id', 'consignee', 'consigneeAddress'];

state.key = 'id';

state.headers = [
    {text: '', value: 'actions', width: 10, sortable: false},
    {text: 'Покупатель', value: 'buyer.SHORTNAME'},
    {text: 'ЭДО', value: 'edo_id'},
    {text: 'Грузополучатель', value: 'consignee'},
    {text: 'Адрес грузополучателя', value: 'consigneeAddress'},

];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
