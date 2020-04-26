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
        item-key="SCODE"
    >
        <template v-slot:body.prepend="{ isMobile }">
            <tr :class="{ 'v-data-table__mobile-table-row' : isMobile }"
                v-if="!isMobile || mobileFiltersVisible"
            >
                <td v-if="!isMobile">
                    <v-btn :disabled="!$store.getters['AUTH/HAS_PERMISSION']('invoice.xlsx')"
                           :loading="saving"
                           @click="save"
                           fab
                           icon
                    >
                        <v-icon color="green">mdi-microsoft-excel</v-icon>
                    </v-btn>
                </td>
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
                                :label="'Позже' + (isMobile ?  ' указанной Даты' : '')"
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
                    <v-text-field :label="isMobile ? 'Номер' : 'Равен'"
                                  :rules="[rules.isInteger]"
                                  v-model="options.filterValues[1]"
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Название покупателя' : 'Начинается с'"
                                  v-model="options.filterValues[4]"
                    />
                </td>
                <td v-if="!isMobile"></td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :rules="[rules.required, rules.isNumber]"
                                  :label="isMobile ? 'Сумма больше' : 'Больше'"
                                  reverse
                                  v-model="options.filterValues[2]"
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :rules="[rules.required, rules.isNumber]"
                                  :label="isMobile ? 'Оплата больше' : 'Больше'"
                                  reverse
                                  v-model="options.filterValues[7]"
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :rules="[rules.required, rules.isNumber]"
                                  :label="isMobile ? 'Отгрузка больше' : 'Больше'"
                                  reverse
                                  v-model="options.filterValues[8]"
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-select :items="statuses"
                              :label="isMobile ? 'Статус' : 'Равен'"
                              v-model="options.filterValues[3]"
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Название фирмы' : 'Начинается с'"
                                  v-model="options.filterValues[6]"
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Фамилия манагера' : 'Начинается с'"
                                  v-model="options.filterValues[5]"
                    />
                </td>
            </tr>
            <tr v-if="isMobile">
                <td>
                    <v-btn @click="mobileFiltersVisible=true" block v-if="!mobileFiltersVisible">
                        Показать фильтры
                    </v-btn>
                    <v-btn @click="mobileFiltersVisible=false" block v-else>Скрыть фильтры</v-btn>
                </td>
            </tr>
        </template>
        <template v-slot:item.actions>

        </template>
        <template v-slot:item.NS="{ item }">
            <router-link :to="{ name: 'invoice', params: { id: item.SCODE } }">
                <v-tooltip top>
                    <template v-slot:activator="{ on }">
                        <span v-on="on">{{ item.NS }}</span>
                    </template>
                    <span>{{ item.PRIM || 'перейти' }}</span>
                </v-tooltip>
            </router-link>
        </template>
        <template v-slot:item.DATA="{ item }">
            {{ item.DATA | formatDate }}
        </template>
        <template v-slot:item.invoiceLinesSum="{ item }">
            {{ item.invoiceLinesSum | formatRub }}
        </template>
        <template v-slot:item.cashFlowsSum="{ item }">
            <div :class="compareToColorText(item.invoiceLinesSum, item.cashFlowsSum)">
                {{ item.cashFlowsSum | formatRub }}
            </div>
        </template>
        <template v-slot:item.transferOutLinesSum="{ item }">
            <div :class="compareToColorText(item.invoiceLinesSum, item.transferOutLinesSum)">
                {{ item.transferOutLinesSum | formatRub }}
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
    import tableMixin from "../mixins/tableMixin";
    import utilsMixin from "../mixins/utilsMixin";
    import tableOptionsRouteMixin from "../mixins/tableOptionsRouteMixin";

    export default {
        name: "Invoices",
        mixins: [tableMixin, tableOptionsRouteMixin, utilsMixin],
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
                    filterValues: [moment().format('Y-MM-DD'), '', 0, [], '', '', '', 0, 0],
                },
                model: 'INVOICE',
                datePicker: false,
                statuses: [
                    {text: 'В работе', value: [0, 1, 2, 3, 4]},
                    {text: 'Без корзины', value: [0, 1, 2, 3, 4, 5]},
                    {text: 'Все', value: []},
                ],
                mobileFiltersVisible: false,
                saving: false,
            }
        },
        computed: {
            ...mapGetters({
                invoiceStatus: 'INVOICESTATUS/GET',
                user: 'AUTH/GET',
            }),
            checkFilters() {
                return this.rules.isInteger(this.options.filterValues[1]) === true
                    && this.rules.isNumber(this.options.filterValues[2]) === true
                    && this.rules.required(this.options.filterValues[2]) === true
                    && this.rules.isNumber(this.options.filterValues[7]) === true
                    && this.rules.required(this.options.filterValues[7]) === true
                    && this.rules.isNumber(this.options.filterValues[8]) === true
                    && this.rules.required(this.options.filterValues[8]) === true;
            }
        },
        methods: {
            compareToColorText(a, b) {
                if (parseFloat(a) > parseFloat(b) && parseFloat(b) === 0) return 'red--text';
                if (parseFloat(a) === parseFloat(b)) return 'green--text';
                return 'primary--text';
            },
            save() {
                this.saving = true;
                this.$store.dispatch('INVOICE/SAVE', this.options)
                    .then(() => {
                    })
                    .catch(() => {
                    })
                    .then(() => this.saving = false);
            }
        },
        beforeRouteEnter(to, from, next) {
            next(vm => {
                vm.$store.commit('BREADCRUMBS/SET', [
                    {
                        text: 'Торговля',
                        to: {name: 'home'},
                        exact: true,
                        disabled: false,
                    },
                    {
                        text: 'Счета',
                        to: {name: 'invoices'},
                        exact: true,
                        disabled: true,
                    }
                ]);
            });
        }
    }
</script>

<style scoped>

</style>
