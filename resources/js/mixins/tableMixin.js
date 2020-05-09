export default {
    data() {
        return {
            loading: false,
            total: 0,
            items: [],
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
                    this.total = response.data.total !== undefined ? response.data.total : response.data.meta.total;
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
        proxyInput(val) {
            this.$emit('input', val);
        }
    },
}
