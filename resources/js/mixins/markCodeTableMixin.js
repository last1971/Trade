// Общая логика таблиц марок ЧЗ (страница + вкладка в товаре):
// словари статусов, ссылки на документы, перевод сортировки по колонкам-связям
// в серверные алиасы MarkCodeService.

// value заголовка → атрибут сортировки на сервере (ключ алиаса-джойна)
const SORT_MAP = {
    'good.name.NAME': 'name.NAME',
    'invoiceLine.invoice.NS': 'invoice.NS',
    'transferOutLine.transferOut.NSF': 'transferOut.NSF',
    'storeLine.orderLine.MASTER_ID': 'orderLine.MASTER_ID',
};

// Статус кода и вид передачи — по одному словарю на каждый: в ячейке таблицы
// нужно короткое слово, в фильтре — с пояснением. Держать два независимых
// списка нельзя: разъедутся молча.
const STATUS = {
    3: {text: 'Наклеен', hint: 'Наклеен (не введён в оборот)'},
    5: {text: 'В обороте'},
    6: {text: 'Выведен из оборота'},
    7: {text: 'Принят с приходом'},
};
const TRANSFER = {
    0: {text: '—', hint: 'Не передан'},
    1: {text: 'УПД', hint: 'УПД юрлицу'},
    2: {text: 'УПД-2', hint: 'УПД-2 (FBO)'},
    3: {text: 'API', hint: 'API маркета (FBS)'},
    4: {text: 'Собств. маркировка', hint: 'Собств. маркировка (S6)'},
};

// Коды, которые вообще бывают: всё прочее в фильтре — мусор из URL или
// localStorage (например ноль от прежнего разбора списков).
export const STATUS_CODES = Object.keys(STATUS).map(Number);
export const TRANSFER_CODES = Object.keys(TRANSFER).map(Number);

// Пункты фильтра: значение списком, потому что фильтр уходит оператором IN.
const filterItems = (dict) => [
    ...Object.entries(dict).map(([value, item]) => ({
        text: item.hint || item.text,
        value: [Number(value)],
    })),
    {text: 'Все', value: []},
];

export default {
    data() {
        return {
            statuses: filterItems(STATUS),
            transferTypes: filterItems(TRANSFER),
        }
    },
    methods: {
        statusText(status) {
            return (STATUS[status] || {}).text || status;
        },
        transferText(type) {
            return (TRANSFER[type] || {}).text || type;
        },
        // Переход на страницу приходов с фильтром по номеру прихода
        storeInLink(np) {
            return {
                name: 'store-ins',
                query: {
                    with: ['seller'],
                    filterAttributes: ['DATA', 'NP', 'seller.NAMEPOST', 'NDOC'],
                    filterOperators: ['>=', '=', 'CONTAIN', 'CONTAIN'],
                    filterValues: ['', String(np), '', ''],
                    sortBy: ['NP'],
                    sortDesc: [true],
                    itemsPerPage: 10,
                    page: 1,
                },
            };
        },
        // tableMixin шлёт на сервер this.requestParams(): подменяем значения
        // заголовков-связей на серверные атрибуты сортировки
        requestParams() {
            const params = _.cloneDeep(this.options);
            params.sortBy = params.sortBy.map((v) => SORT_MAP[v] || v);
            return params;
        },
    },
}
