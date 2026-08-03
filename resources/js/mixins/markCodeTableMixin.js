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

export default {
    data() {
        return {
            statuses: [
                {text: 'Наклеен (не введён в оборот)', value: [3]},
                {text: 'В обороте', value: [5]},
                {text: 'Выведен из оборота', value: [6]},
                {text: 'Принят с приходом', value: [7]},
                {text: 'Все', value: []},
            ],
            transferTypes: [
                {text: 'Не передан', value: [0]},
                {text: 'УПД юрлицу', value: [1]},
                {text: 'УПД-2 (FBO)', value: [2]},
                {text: 'API маркета (FBS)', value: [3]},
                {text: 'Собств. маркировка (S6)', value: [4]},
                {text: 'Все', value: []},
            ],
        }
    },
    methods: {
        statusText(status) {
            return {
                3: 'Наклеен',
                5: 'В обороте',
                6: 'Выведен из оборота',
                7: 'Принят с приходом',
            }[status] || status;
        },
        transferText(type) {
            return {
                0: '—',
                1: 'УПД',
                2: 'УПД-2',
                3: 'API',
                4: 'Собств. маркировка',
            }[type] || type;
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
