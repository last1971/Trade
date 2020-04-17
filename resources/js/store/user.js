import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'user';

state.key = 'id';

state.fillable = ['name', 'employeeId', 'roles', 'user_buyers'];

state.headers = [
    // {text: '', value: 'actions', width: 10, sortable: false},
    {
        text: 'Имя',
        value: 'name'
    },
    {
        text: 'Мыло',
        value: 'email',
    },
    {
        text: 'Сотрудник',
        value: 'employeeId',
        sortable: false,
    },
    {
        text: 'Роли',
        value: 'roles',
        sortable: false,
    },
    {
        text: 'Покупатели',
        value: 'userBuyers',
        sortable: false,
    }

];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
