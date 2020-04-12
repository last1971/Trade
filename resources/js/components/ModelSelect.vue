<template>
    <v-autocomplete
        :auto-select-first="true"
        :chips="multiple"
        :clearable="!multiple"
        :deletable-chips="multiple"
        :dense="dense"
        :disabled="disabled"
        :error="!proxy"
        :item-text="itemText"
        :item-value="itemValue"
        :items="items"
        :label="label"
        :loading="isLoading"
        :multiple="multiple"
        :no-filter="true"
        :search-input.sync="search"
        hide-no-data
        hide-selected
        placeholder="поиска"
        v-model="proxy"
    >
        <template v-slot:prepend>
            <slot name="prepend"></slot>
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
            filterAttributes: {type: Array, default: () => []},
            filterOperators: {type: Array, default: () => []},
            filterValues: {type: Array, default: () => []},
            sortBy: {type: Array, default: () => []},
            sortDesc: {type: Array, default: () => [false]},
            disabled: {type: Boolean, default: false},
            dense: {type: Boolean, default: false},
        },
        data() {
            return {
                items: [],
                isLoading: false,
                search: '',
            }
        },
        created() {
            if (this.itemsPerPage < 0) {
                this.getItems();
            }
            if (this.value) {
                if (_.isArray(this.value) && this.value.length > 0) {
                    this.isLoading = true;
                    this.$store.dispatch(this.MODEL + '/ALL', this.options)
                        .then((response) => this.items = response.data.data)
                        .catch(() => {
                        })
                        .then(() => this.isLoading = false);
                } else {
                    this.getItem();
                }
            }
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
            MODEL() {
                return _.toUpper(this.model);
            }
        },
        watch: {
            search: _.debounce(function (val) {
                // case when not need update data from server
                if (!val || this.isLoading || this.itemsPerPage < 0) return;
                // error for multiple possible will
                const proxy = this.$store.getters[this.MODEL + '/GET'](this.value);
                if (this.value && val === _.property(this.itemText)(proxy)) return;
                this.getItems(val);
            }, 500),
            value() {
                this.getItem();
            },
        },
        methods: {
            getItems(val = '') {
                const options = {
                    itemsPerPage: this.itemsPerPage,
                    filterAttributes: _.concat(this.filterAttributes, this.itemText),
                    filterOperators: _.concat(this.filterOperators, 'CONTAIN'),
                    filterValues: _.concat(this.filterValues, val),
                    sortBy: _.isEmpty(this.sortBy) ? [this.itemText] : this.sortBy,
                    sortDesc: this.sortDesc
                };
                this.isLoading = true;
                this.$store.dispatch(this.MODEL + '/ALL', options)
                    .then((response) => {
                        const filtred = _.isArray(this.value)
                            ? this.items.filter((item) => this.value.indexOf(item[this.itemValue]) >= 0)
                            : [];
                        this.items = _.union(response.data.data, filtred);
                    })
                    .then(() => this.isLoading = false);
            },
            getItem() {
                if (this.value && !_.isArray(this.value)) {
                    this.isLoading = true;
                    this.$store.dispatch(this.MODEL + '/CACHE', this.value)
                        .then((model) => {
                            if (!_.find(this.items, {[this.itemValue]: model[this.itemValue]}))
                                this.items.push(model)
                        })
                        .then(() => this.isLoading = false);
                }
            }
        }
    }
</script>

<style scoped>
</style>