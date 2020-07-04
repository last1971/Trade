export default {
    data() {
        return {
            loading: false,
            total: 0,
            // items: [],
            itemIds: [],
            copyItems: [],
            dependent: false,
            parent: null,
            filterValues: null,
        }
    },
    computed: {
        headers() {
            const headers = this.$store.getters[this.model + '/HEADERS'];
            return headers ? headers.filter((v) => {
                return !v.full
                    || this.$store.getters['AUTH/HAS_PERMISSION'](_.toLower(this.model) + '.full');
            }) : [];
        },
        checkFilters() {
            return true;
        },
        items() {
            return this.$store.getters[this.model + '/GET'](this.itemIds);
        }
    },
    watch: {
        options: {
            handler: _.debounce(function (newVal, oldVal) {
                if (
                    oldVal.page &&
                    newVal.page === oldVal.page &&
                    this.filterValues &&
                    !_.isEqual(this.filterValues, newVal.filterValues)
                ) {
                    this.options.page = 1;
                }
                this.filterValues = _.cloneDeep(newVal.filterValues);
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
            if (!this.checkFilters || !this.options.page) return;
            this.loading = true;
            this.$store.dispatch(this.model + '/ALL', this.requestParams())
                .then((response) => {
                    this.total = response.total;//.data.total !== undefined ? response.data.total : response.data.meta.total;
                    this.itemIds = response.itemIds;
                    this.copyItmes = response.copyItmes;
                    const newQuery = _.cloneDeep(this.options);
                    if (!this.dependent && !_.isEqual(this.$route.query, newQuery)) {
                        this.$router.replace(
                            {name: this.$route.name, params: this.$route.params, query: newQuery}
                        );
                    }
                    this.setLinks();
                })
                .catch(() => {
                })
                .then(() => this.loading = false)
        },
        proxyInput(val) {
            this.$emit('input', val);
        },
        save(item) {
            this.$store.dispatch(this.model + '/UPDATE', {item, options: this.options});
        },
        setLinks() {
            this.copyItmes.forEach((item, index) => {
                let previous, next;
                const {page, itemsPerPage} = this.options;
                const lastPage = Math.floor((this.total - 1) / itemsPerPage) + 1;
                if (index === 0) {
                    const newQuery = _.cloneDeep(this.options);
                    newQuery.page = page > 1 ? page - 1 : lastPage;
                    previous = {name: this.$route.name, params: this.$route.params, query: newQuery};
                } else {
                    previous = {
                        name: this.$route.name.slice(0, -1),
                        params: {id: this.copyItmes[index - 1][this.$store.getters[this.model + '/KEY']]}
                    };
                }
                if (index + 1 === this.copyItmes.length) {
                    const newQuery = _.cloneDeep(this.options);
                    newQuery.page = page === lastPage ? 1 : page + 1;
                    next = {name: this.$route.name, params: this.$route.params, query: newQuery};
                } else {
                    next = {
                        name: this.$route.name.slice(0, -1),
                        params: {id: this.copyItmes[index + 1][this.$store.getters[this.model + '/KEY']]}
                    };
                }
                this.$store.commit(this.model + '/SET_LINK', {
                    key: item[this.$store.getters[this.model + '/KEY']],
                    link: {
                        previous,
                        next,
                    }
                });
            });
        }
    },
}
