import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'unit-code-alias';

state.key = 'id';

state.items = [
    {id: 0}
]

state.fillable = ['name', 'unit_code_id'];

state.headers = [
    //text: '', value: 'actions', width: 10, sortable: false},
    {
        text: 'Наименование',
        value: 'name',
    },
];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
