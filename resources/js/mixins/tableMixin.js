export default {
    data() {
        return {
            loading: false,
            total: 0,
            items: [],
            dependent: false,
            parent: null,
        }
    },
    computed: {
        headers() {
            return this.$store.getters[this.model + '/HEADERS'];
        },
        model() {
            return this.$route.meta.model;
        },
        checkFilters() {
            return true;
        }
    },
    watch: {
        options: {
            handler: _.debounce(function () {
                this.updateItems();
            }, 500),
            deep: true
        },
    },
    created() {
        this.updateItems();
    },
    methods: {
        requestParams() {
            return this.options;
        },
        updateItems() {
            if (!this.checkFilters || this.loading) return;
            this.loading = true;
            // if (this.$route.query.page === this.options.page && !this.dependent) this.options.page = 1;
            this.$store.dispatch(this.model + '/ALL', this.requestParams())
                .then((response) => {
                    this.total = response.data.total;
                    this.items = response.data.data;
                    const newQuery = _.cloneDeep(this.options);
                    if (!this.dependent && !_.isEqual(this.$route.query, newQuery)) {
                        this.$router.replace(
                            {name: this.$route.name, params: this.$route.params, query: newQuery}
                        );
                    }
                })
                .catch(() => {
                })
                .then(() => this.loading = false)
        },
    },
    beforeRouteEnter(to, from, next) {
        next(vm => {
            let options = vm.options;
            if (!_.isEmpty(to.query) && !vm.dependent) {
                options = to.query;
            } else {
                const localOptions = vm.$store.getters['USER/LOCAL_OPTION'](to.meta.model);
                if (localOptions) options = localOptions;
            }
            options.itemsPerPage = parseInt(options.itemsPerPage);
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
            vm.options = options;
        });
    },
    beforeRouteLeave(to, from, next) {
        if (to.name !== 'login') {
            this.$store.commit('USER/SET_LOCAL_OPTION', {[this.model]: this.options});
        }
        next();
    }
}
