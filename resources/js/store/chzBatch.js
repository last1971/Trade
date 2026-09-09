import model from './model'
import _ from 'lodash'

let state = _.cloneDeep(model.state);

state.name = 'chz-outbox';

state.key = 'ID';

// Колонки страницы «Отправка в Честный знак». Номера счёта и УПД приходят
// джойнами ChzBatchService — по ним же идут фильтр и сортировка.
state.headers = [
    {text: '№', value: 'ID'},
    {text: 'Вид', value: 'KIND'},
    {text: 'Статус', value: 'STATUS'},
    {text: 'Кодов', value: 'CNT', align: 'end'},
    {text: 'Счёт', value: 'invoice.NS'},
    {text: 'УПД', value: 'transferOut.NSF'},
    {text: 'Создана', value: 'CREATED_AT'},
    {text: 'Отправлена', value: 'SENT_AT'},
    {text: 'Принято', value: 'CONFIRMED_AT'},
    {text: 'Отчёт / документ', value: 'DOC_UUID', sortable: false},
    {text: 'Кем', value: 'CREATED_BY'},
    {text: '', value: 'actions', sortable: false},
];

export default {
    namespaced: true,
    state,
    getters: model.getters,
    mutations: model.mutations,
    actions: model.actions,
}
