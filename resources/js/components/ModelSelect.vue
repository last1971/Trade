<template>
    <v-autocomplete
        :auto-select-first="true"
        :chips="multiple"
        :clearable="!multiple"
        :deletable-chips="multiple"
        :dense="dense"
        :disabled="disabled"
        :error="!proxy && !canEmpty"
        :error-messages="errorMessages"
        :item-text="itemText"
        :item-value="itemValue"
        :items="items"
        :label="label"
        :loading="isLoading"
        :multiple="multiple"
        :no-filter="noFilter"
        :search-input.sync="search"
        hide-no-data
        hide-selected
        placeholder="поиск"
        v-model="proxy"
        @focus="$emit('focus')"
        :filled="isNotEmpty"
    >
        <template v-slot:prepend>
            <slot name="prepend"></slot>
        </template>
        <template v-slot:append>
            <slot name="append"></slot>
        </template>
        <template v-slot:item="{ item }">
            <slot name="item" v-bind:item="item" v-bind:maxLength="maxLength">
                {{ item[itemText] }}
            </slot>
        </template>
        <template v-if="!multiple" v-slot:selection="{ item }">
            <slot name="selection" v-bind:item="item">
                {{ item[itemText] }}
            </slot>
        </template>
    </v-autocomplete>
</template>

<script>
    import _ from 'lodash';

    export default {
        name: "ModelSelect",
        props: {
            value: {type: [Array, Number, String]},
            multiple: {type: Boolean, default: false},
            label: {type: String, required: true},
            itemText: {type: String, default: 'name'},
            itemValue: {type: String, default: 'id'},
            model: {type: String, required: true},
            itemsPerPage: {type: Number, default: 10},
            with: {type: Array, default: () => []},
            aggregateAttributes: {type: Array, default: () => []},
            filterAttributes: {type: Array, default: () => []},
            filterOperators: {type: Array, default: () => []},
            filterValues: {type: Array, default: () => []},
            sortBy: {type: Array, default: () => []},
            sortDesc: {type: Array, default: () => []},
            disabled: {type: Boolean, default: false},
            dense: {type: Boolean, default: false},
            errorMessages: {type: Array, default: () => []},
            noFilter: {type: Boolean, default: true},
            getValue: {type: Boolean, default: false},
            newSearch: {type: String, default: ''},
            smartName: {type: Boolean, default: false},
            canEmpty: {type: Boolean, default: false}
        },
        data() {
            return {
                itemIds: [],
                isLoading: false,
                search: null,
            }
        },
        created() {
            if (this.itemsPerPage < 0) {
                const items = this.$store.getters[this.MODEL + '/ALL'];
                if (_.isEmpty(items)) {
                    this.getItems();
                } else {
                    this.itemIds = items.map((item) => item[this.itemValue]);
                    //this.items = items;
                }
            }
            this.loadValue();
        },
        computed: {
            proxy: {
                get() {
                    return this.value
                },
                set(val) {
                    this.$emit('input', val)
                }
            },
            maxLength() {
                const maxName = _.maxBy(this.items, (item) => (_.get(item, this.itemText) || '').length)
                return maxName ? _.get(maxName, this.itemText).length : 0;
            },
            MODEL() {
                return _.toUpper(this.model);
            },
            items() {
                return this.$store.getters[this.MODEL + '/GET'](this.itemIds);
            },
            isNotEmpty() {
                return !!(_.isArray(this.proxy) && !_.isEmpty(this.proxy)) || !!(!_.isArray(this.proxy) && this.proxy);
            }
        },
        watch: {
            search: _.debounce(function (val) {
                // case when not need update data from server
                if (!val || this.isLoading || this.itemsPerPage < 0) return;
                this.$emit('search', val);
                // error for multiple possible will
                const proxy = this.$store.getters[this.MODEL + '/GET'](this.value);
                if (this.value && val === _.property(this.itemText)(proxy)) return;
                this.getItems(val);
            }, 1500),
            value() {
                this.loadValue();
            },
            getValue(val) {
                if (val) this.loadValue();
            },
            newSearch(val) {
                if (!!val) {
                    this.search = val;
                    this.$emit('clearSearchName');
                }
            }
        },
        methods: {
            async getItems(val = '') {
                const options = {
                    itemsPerPage: this.itemsPerPage,
                    aggregateAttributes: this.aggregateAttributes,
                    filterAttributes: _.concat(this.filterAttributes, this.itemText),
                    filterOperators: _.concat(this.filterOperators, 'CONTAIN'),
                    filterValues: _.concat(this.filterValues, val.substr(0,69)),
                    sortBy: _.isEmpty(this.sortBy) ? null : this.sortBy,
                    sortDesc: _.isEmpty(this.sortDesc) ? null: this.sortDesc,
                    with: this.with,
                    smartName: this.smartName,
                };
                this.isLoading = true;
                const response = await this.$store.dispatch(this.MODEL + '/ALL', options);
                const filtred = _.isArray(this.value)
                    ? this.items.filter((item) => this.value.indexOf(item[this.itemValue]) >= 0)
                    : [];
                this.itemIds = _.union(response.itemIds, filtred.map((v) => v[this.itemValue]))
                // this.items = _.union(response.copyItems, filtred);
                this.isLoading = false;
            },
            getItem() {
                if (this.value && !_.isArray(this.value)) {
                    this.isLoading = true;
                    this.$store.dispatch(this.MODEL + '/CACHE', {id: this.value, query: {with: this.with}})
                        .then((model) => {
                            if (!_.find(this.items, {[this.itemValue]: model[this.itemValue]})) {
                                this.itemIds.push(model[this.itemValue]);
                                // this.items.push(model)
                            }
                        })
                        .then(() => this.isLoading = false);
                }
            },
            loadValue() {
                if (this.value) {
                    if (_.isArray(this.value) && this.value.length > 0 && this.itemsPerPage > 0) {
                        this.isLoading = true;
                        const options = {
                            itemsPerPage: this.itemsPerPage,
                            with: this.with,
                            filterAttributes: _.concat(this.filterAttributes, this.itemValue),
                            filterOperators: _.concat(this.filterOperators, 'IN'),
                            filterValues: _.concat(this.filterValues, [this.value]),
                            sortBy: _.isEmpty(this.sortBy) ? null : this.sortBy,
                            sortDesc: _.isEmpty(this.sortDesc) ? null: this.sortDesc,
                            //sortBy: _.isEmpty(this.sortBy) ? [this.itemText] : this.sortBy,
                            //sortDesc: this.sortDesc
                        };
                        this.$store.dispatch(this.MODEL + '/ALL', options)
                            .then((response) => {
                                this.itemIds = response.itemIds;
                                // this.items = response.copyItems
                            })
                            .catch((e) => {
                                console.error(e);
                            })
                            .then(() => this.isLoading = false);
                    } else {
                        this.getItem();
                    }
                }
            }
        }
    }
</script>

<style scoped>
</style>
