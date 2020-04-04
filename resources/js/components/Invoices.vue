<template>
    <v-data-table
        :headers="headers"
        :items="items"
        :loading="loading"
        :multi-sort="true"
        :options.sync="options"
        :server-items-length="total"
        loading-text="Loading... Please wait"
    >
        <template v-slot:top>

        </template>
        <template v-if="this.$vuetify.breakpoint.mdAndUp" v-slot:body.prepend>
            <tr>
                <td></td>
                <td>
                    <v-menu
                        :close-on-content-click="false"
                        :nudge-right="40"
                        min-width="290px"
                        offset-y
                        transition="scale-transition"
                        v-model="datePicker"
                    >
                        <template v-slot:activator="{ on }">
                            <v-text-field
                                :value="options.filterValues[0] | formatDate"
                                label="Позже"
                                prepend-icon="mdi-calendar-edit"
                                readonly
                                v-on="on"
                            />
                        </template>
                        <v-date-picker @input="datePicker = false" v-model="options.filterValues[0]"/>
                    </v-menu>
                </td>
                <td>
                    <v-text-field label="Номер" v-model="options.filterValues[1]"/>
                </td>
                <td></td>
                <td></td>
                <td>
                    <v-text-field label="Больше" reverse v-model="options.filterValues[2]"/>
                </td>
            </tr>
        </template>
        <template v-slot:item.actions>

        </template>
        <template v-slot:item.DATA="{ item }">
            {{ item.DATA | formatDate }}
        </template>
        <template v-slot:item.STATUS="{ item }">
            {{ invoiceStatus(item.STATUS) }}
        </template>
    </v-data-table>
</template>

<script>
    import moment from 'moment';
    import {mapGetters} from 'vuex';

    export default {
        name: "Invoices",
        data() {
            return {
                options: {
                    with: ['buyer'],
                    aggregateAttributes: ['invoiceLinesCount', 'invoiceLinesSum'],
                    filterAttributes: ['DATA', 'NS', 'invoiceLinesSum'],
                    filterOperators: ['>', 'LIKE', '>'],
                    filterValues: [moment().format('Y-MM-DD'), '', 0],
                },
                loading: false,
                total: 0,
                items: [],
                dependent: false,
                datePicker: false,
            }
        },
        computed: {
            headers() {
                return this.$store.getters[this.model + '/HEADERS'];
            },
            model() {
                return this.$route.meta.model;
            },
            ...mapGetters({
                invoiceStatus: 'INVOICESTATUS/GET'
            }),
        },
        watch: {
            options: {
                handler: _.debounce(function () {
                    this.updateItems();
                }, 500),
                deep: true
            }
        },
        created() {
            this.$store.dispatch('USER/OPTIONS', this.model)
                .then((response) => {
                    if (!_.isEmpty(response)) {
                        const convert = response;
                        convert.filterValues = convert.filterValues.map((v) => v === null ? '' : v);
                        this.options = convert;
                    }
                })
        },
        methods: {
            updateItems() {
                this.loading = true;
                // if (this.$route.query.page === this.options.page && !this.dependent) this.options.page = 1;
                this.$store.dispatch('USER/OPTION', {option: this.model, value: this.options});
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
            requestParams() {
                return this.options;
            }
        },
    }
</script>

<style scoped>

</style>
