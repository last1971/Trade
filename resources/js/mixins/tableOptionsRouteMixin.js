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
                options.sortDesc = typeof options.sortDesc === 'string' ? [options.sortDesc] : options.sortDesc;
            }
            if (options.multiSort) {
                options.multiSort = options.multiSort === "true" || options.multiSort === true;
            }
            if (options.mustSort) {
                options.mustSort = options.mustSort === "true" || options.multiSort === true;
            }
            if (options.filterAttributes) {
                options.filterAttributes = typeof options.filterAttributes === 'string' ?
                    [options.filterAttributes] : options.filterAttributes;
                options.filterOperators = typeof options.filterOperators === 'string' ?
                    [options.filterOperators] : options.filterOperators;
                options.filterValues = typeof options.filterValues === 'string' ?
                    [options.filterValues] : options.filterValues;
                options.filterOperators.forEach((operator, index) => {
                    if (operator === 'IN' && typeof options.filterValues[index] === 'string') {
                        options.filterValues[index] = _.split(options.filterValues[index], ',');
                        options.filterValues[index] = options.filterValues[index].map((v) => _.toInteger(v));
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
