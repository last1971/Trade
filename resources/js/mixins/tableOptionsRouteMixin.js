/**
 * Значения фильтра IN, вернувшиеся из URL или localStorage, — снова списком.
 *
 * Список из нескольких значений уезжает в адрес строкой JSON (["retire"],
 * [3,5]), из localStorage приходит как есть. Числа из строки надо вернуть
 * числами, а слова оставить словами: бывший здесь безусловный toInteger
 * превращал «retire» в 0, и после возврата на страницу браузерным «назад»
 * список молча оказывался пустым — фильтр уходил на сервер как KIND IN (0).
 */
function parseInValues(raw) {
    const text = String(raw).trim();
    if (_.isEmpty(text)) {
        return [];
    }
    if (_.startsWith(text, '[')) {
        try {
            const parsed = JSON.parse(text);
            if (Array.isArray(parsed)) {
                return parsed;
            }
        } catch (e) {
            // не JSON — разбираем как перечисление через запятую
        }
    }
    return _.split(text, ',').map((value) => {
        const item = _.trim(value);
        return /^-?\d+$/.test(item) ? _.toInteger(item) : item;
    });
}

export default {
    beforeRouteEnter(to, from, next) {
        next(vm => {
            let options = vm.options;
            if (!_.isEmpty(to.query) && !vm.dependent) {
                options = to.query;
            } else if (!vm.dependent) {
                const localOptions = vm.$store.getters['AUTH/LOCAL_OPTION'](to.meta.model);
                if (localOptions) options = localOptions;
            }
            options.itemsPerPage = parseInt(options.itemsPerPage);
            options.page = parseInt(options.page);
            if (options.with) {
                options.with = typeof options.with === 'string' ? [options.with] : options.with;
            }
            if (options.sortBy) {
                options.sortBy = typeof options.sortBy === 'string' ? [options.sortBy] : options.sortBy;
            }
            if (options.sortDesc) {
                // Из URL приходят строки "true"/"false"; строка "false" истинна,
                // и v-data-table считает колонку уже отсортированной по убыванию —
                // цикл «вверх → вниз → снять» ломается. Приводим к boolean.
                if (!Array.isArray(options.sortDesc)) options.sortDesc = [options.sortDesc];
                options.sortDesc = options.sortDesc.map((desc) => desc === true || desc === "true");
            }
            if (options.multiSort) {
                options.multiSort = options.multiSort === "true" || options.multiSort === true;
            }
            // mustSort — часть кода, а не настройка: таблицы его не задают (кроме
            // Payments, где false). Выбрасываем протухшее значение из URL/localStorage,
            // иначе залипший mustSort=true не даёт снять сортировку кликом по колонке.
            delete options.mustSort;
            if (options.filterAttributes) {
                options.filterAttributes = typeof options.filterAttributes === 'string' ?
                    [options.filterAttributes] : options.filterAttributes;
                options.filterOperators = typeof options.filterOperators === 'string' ?
                    [options.filterOperators] : options.filterOperators;
                options.filterValues = typeof options.filterValues === 'string' ?
                    [options.filterValues] : options.filterValues;
                options.filterOperators.forEach((operator, index) => {
                    if (operator === 'IN' && typeof options.filterValues[index] === 'string') {
                        options.filterValues[index] = parseInValues(options.filterValues[index]);
                    }
                })
            }
            vm.options = options;
        });
    },
    beforeRouteLeave(to, from, next) {
        if (to.name !== 'login' && to.name !== 'home') {
            this.$store.commit('AUTH/SET_LOCAL_OPTION', {[this.model]: this.options});
        }
        next();
    }
}
