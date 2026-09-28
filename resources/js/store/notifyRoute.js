import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'notify-route';

state.key = 'ID';

state.fillable = ['TOPIC', 'CHANNEL', 'TARGET', 'ENABLED'];

// Страница «Маршруты уведомлений»: тема → канал → адресат. Таблица NOTIFY_ROUTE
// одна на базу, её же читает ozon — правка здесь действует на обе программы.
state.headers = [
    {text: 'Тема', value: 'TOPIC'},
    {text: 'Канал', value: 'CHANNEL'},
    {text: 'Адресат', value: 'TARGET'},
    {text: 'Вкл.', value: 'ENABLED', align: 'center'},
    {text: '', value: 'actions', sortable: false, align: 'end'},
];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
