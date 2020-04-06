<template>
    <v-data-table
        :headers="headers"
        :items="items"
        :loading="loading"
        :multi-sort="true"
        :options.sync="options"
        :server-items-length="total"
        loading-text="Loading... Please wait"
        :footer-props="{
            showFirstLastPage: true,
        }"
    >
        <template v-slot:top>

        </template>
        <template v-slot:body.prepend="{ isMobile }">
            <tr :class="{ 'v-data-table__mobile-table-row' : isMobile }">
                <td v-if="!isMobile"></td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
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
                        <v-date-picker @input="datePicker = false"
                                       first-day-of-week="1"
                                       v-model="options.filterValues[0]"
                        />
                    </v-menu>
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :rules="[rules.isInteger]" label="Равен" v-model="options.filterValues[1]"/>
                </td>
                <td class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field label="Начинается с" v-model="options.filterValues[4]"/>
                </td>
                <td v-if="!isMobile"></td>
                <td class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :rules="[rules.required, rules.isNumber]"
                                  label="Больше"
                                  reverse
                                  v-model="options.filterValues[2]"
                    />
                </td>
                <td class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :rules="[rules.required, rules.isNumber]"
                                  label="Больше"
                                  reverse
                                  v-model="options.filterValues[7]"
                    />
                </td>
                <td class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :rules="[rules.required, rules.isNumber]"
                                  label="Больше"
                                  reverse
                                  v-model="options.filterValues[8]"
                    />
                </td>
                <td class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-select :items="statuses" label="Равен" v-model="options.filterValues[3]"/>
                </td>
                <td class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field label="Начинается с" v-model="options.filterValues[6]"/>
                </td>
                <td class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field label="Начинается с" v-model="options.filterValues[5]"/>
                </td>
            </tr>
        </template>
        <template v-slot:item.actions>

        </template>
        <template v-slot:item.DATA="{ item }">
            {{ item.DATA | formatDate }}
        </template>
        <template v-slot:item.cashFlowsSum="{ item }">
            <div :class="compareToColorText(item.invoiceLinesSum, item.cashFlowsSum)">
                {{ item.cashFlowsSum }}
            </div>
        </template>
        <template v-slot:item.transferOutLinesSum="{ item }">
            <div :class="compareToColorText(item.invoiceLinesSum, item.transferOutLinesSum)">
                {{ item.transferOutLinesSum }}
            </div>
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
                    with: ['buyer', 'employee', 'firm'],
                    aggregateAttributes: [
                        'invoiceLinesCount', 'invoiceLinesSum', 'cashFlowsSum', 'transferOutLinesSum'
                    ],
                    filterAttributes: [
                        'DATA',
                        'NS',
                        'invoiceLinesSum',
                        'STATUS',
                        'buyer.SHORTNAME',
                        'employee.FULLNAME',
                        'firm.FIRMNAME',
                        'cashFlowsSum',
                        'transferOutLinesSum',
                    ],
                    filterOperators: ['>=', 'LIKE', '>=', 'IN', 'CONTAIN', 'CONTAIN', 'CONTAIN', '>=', '>='],
                    filterValues: [moment().format('Y-MM-DD'), '', 0, '0,1,2,3,4', '', '', '', 0, 0],
                },
                loading: false,
                total: 0,
                items: [],
                dependent: false,
                datePicker: false,
                statuses: [
                    {text: 'В работе', value: '0,1,2,3,4'},
                    {text: 'Без корзины', value: '0,1,2,3,4,5'},
                    {text: 'Все', value: '0,1,2,3,4,5,6'},
                ],
                rules: {
                    isInteger: n => _.isInteger(_.toNumber(n)) || 'Введите целое число',
                    isNumber: n => !_.isNaN(_.toNumber(n)) || 'Введите число',
                    required: v => (v === 0 || !!v) || 'Обязателный'
                }
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
            checkFilters() {
                return this.rules.isInteger(this.options.filterValues[1]) === true
                    && this.rules.isNumber(this.options.filterValues[2]) === true
                    && this.rules.required(this.options.filterValues[2]) === true;
            }
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
            /*this.$store.dispatch('USER/OPTIONS', this.model)
                .then((response) => {
                    if (!_.isEmpty(response)) {
                        const convert = response;
                        convert.filterValues = convert.filterValues.map((v) => v === null ? '' : v);
                        this.options = convert;
                    }
                })*/
        },
        methods: {
            compareToColorText(a, b) {
                if (parseFloat(a) > parseFloat(b) && parseFloat(b) === 0) return 'red--text';
                if (parseFloat(a) === parseFloat(b)) return 'green--text';
                return 'primary--text';
            },
            requestParams() {
                return this.options;
            },
            updateItems() {
                if (!this.checkFilters) return;
                this.loading = true;
                // if (this.$route.query.page === this.options.page && !this.dependent) this.options.page = 1;
                // this.$store.dispatch('USER/OPTION', {option: this.model, value: this.options});
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
                if (!_.isEmpty(to.query)) {
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
</script>

<style scoped>

</style>
