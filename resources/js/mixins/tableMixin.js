export default {
    data() {
        return {
            loading: false,
            total: 0,
            items: [],
            dependent: false,
        }
    },
    computed: {
        headers() {
            return this.$store.getters[this.model + '/HEADERS'];
        },
        model() {
            return this.$route.meta.model;
        },
    },
    watch: {
        options: {
            handler: _.debounce(function () {
                this.updateItems();
            }, 500),
            deep: true
        }
    },
    methods: {
        requestParams() {
            return this.options;
        },
        updateItems() {
            if (!this.checkFilters) return;
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
    }
}
