export default {
    data() {
        return {
            loading: false,
            loadingText: 'Идут разгрузочно-погрузочные работы... будь пациентом',
            total: 0,
            // items: [],
            itemIds: [],
            selectedIds: [],
            copyItems: [],
            dependent: false,
            parent: null,
            filterValues: null,
        }
    },
    computed: {
        headers() {
            const headers = this.$store.getters[this.model + '/HEADERS'].filter((header) => !header.hidden);
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
                    oldVal.page
                    &&
                    newVal.page === oldVal.page
                    &&
                    this.filterValues
                    &&
                    !_.isEqual(this.filterValues, newVal.filterValues)
                ) {
                    this.options.page = 1;
                }
                this.filterValues = _.cloneDeep(newVal.filterValues);
                this.updateItems();
            }, 1000),
            deep: true
        },
    },
    created() {
        this.$root.$on('create', (a) => {
            if (a.model === this.model) this.updateItems(false);
        });
    },
    methods: {
        requestParams() {
            return this.options;
        },
        updateItems(changeRoute = true) {
            if (!this.checkFilters || !this.options.page) return;
            this.loading = true;
            this.$store.dispatch(this.model + '/ALL', this.requestParams())
                .then((response) => {
                    this.total = response.total;//.data.total !== undefined ? response.data.total : response.data.meta.total;
                    this.itemIds = response.itemIds;
                    this.copyItems = response.copyItems;
                    const newQuery = _.cloneDeep(this.options);
                    if (!this.dependent && !_.isEqual(this.$route.query, newQuery) && changeRoute) {
                        this.$router.replace(
                            {params: this.$route.params, query: newQuery}
                        );
                        this.setLinks();
                    }
                })
                .catch(() => {
                })
                .then(() => this.loading = false)
        },
        proxyInput(val) {
            if (this.model === 'INVOICE-LINE' && val.STATUS !== this.value.STATUS) {
                this.updateItems(false);
            }
            this.$emit('input', val);
        },
        async save(item) {
            await this.$store.dispatch(this.model + '/UPDATE', {item, options: this.options});
            await this.reloadValue();
        },
        async reloadValue() {
            //
        },
        setLinks() {
            this.copyItems.forEach((item, index) => {
                let previous, next;
                const {page, itemsPerPage} = this.options;
                const lastPage = Math.floor((this.total - 1) / itemsPerPage) + 1;
                if (index === 0) {
                    const newQuery = _.cloneDeep(this.options);
                    newQuery.page = page > 1 ? page - 1 : lastPage;
                    previous = {params: this.$route.params, query: newQuery};
                } else {
                    previous = {
                        name: this.$route.name.slice(0, -1),
                        params: {id: this.copyItems[index - 1][this.$store.getters[this.model + '/KEY']]}
                    };
                }
                if (index + 1 === this.copyItems.length) {
                    const newQuery = _.cloneDeep(this.options);
                    newQuery.page = page === lastPage ? 1 : page + 1;
                    next = {params: this.$route.params, query: newQuery};
                } else {
                    next = {
                        name: this.$route.name.slice(0, -1),
                        params: {id: this.copyItems[index + 1][this.$store.getters[this.model + '/KEY']]}
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
        },
        selectItem(payload) {
            const index = this.selectedIds.indexOf(payload.item[this.$store.getters[this.model + '/KEY']]);
            if (payload.value && index < 0) {
                this.selectedIds.push(payload.item[this.$store.getters[this.model + '/KEY']]);
            } else if (!payload.value && index > -1) {
                this.selectedIds.splice(index, 1);
            }
        },
        selectItems(payload) {
            const { value, items } = payload;
            items.forEach((item) => this.selectItem({ item, value }));
        }
    },
}
