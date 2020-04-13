<template>
    <v-data-table
        :footer-props="{
            showFirstLastPage: true,
        }"
        :headers="headers"
        :items="items"
        :loading="loading"
        :multi-sort="true"
        :options.sync="options"
        :server-items-length="total"
        item-key="ID"
        loading-text="Loading... Please wait"
    >
        <template v-slot:body.prepend="{ isMobile }">
            <tr
                v-if="!isMobile || mobileFiltersVisible"
            >
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
                                :label="'Позже' + (isMobile ?  ' указанной Даты' : '')"
                                :value="options.filterValues[0] | formatDate"
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
                    <v-text-field :label="isMobile ? 'Номер' : 'Содержит'"
                                  :rules="[rules.isInteger]"
                                  v-model="options.filterValues[1]"
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Поставщик' : 'Начинается с'"
                                  v-model="options.filterValues[2]"
                    />
                </td>
                <td v-if="!isMobile"></td>
                <td v-if="!isMobile"></td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Сумма больше' : 'Больше'"
                                  :rules="[rules.required, rules.isNumber]"
                                  reverse
                                  v-model="options.filterValues[3]"
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Оплата больше' : 'Больше'"
                                  :rules="[rules.required, rules.isNumber]"
                                  reverse
                                  v-model="options.filterValues[4]"
                    />
                </td>
                <td v-if="!isMobile"></td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-select :items="statuses"
                              :label="isMobile ? 'Статус' : 'Равен'"
                              v-model="options.filterValues[5]"
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Фамилия манагера' : 'Начинается с'"
                                  v-model="options.filterValues[6]"
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
        <template v-slot:item.INVOICE_NUM="{ item }">
            <router-link :to="{ name: 'home' }">
                <v-tooltip top>
                    <template v-slot:activator="{ on }">
                        <span v-on="on">{{ item.INVOICE_NUM }}</span>
                    </template>
                    <span>{{ item.PRIM || 'перейти' }}</span>
                </v-tooltip>
            </router-link>
        </template>
        <template v-slot:item.INVOICE_DATA="{ item }">
            {{ item.INVOICE_DATA | formatDate }}
        </template>
        <template v-slot:item.DATA_PRIH="{ item }">
            {{ item.DATA_PRIH | formatDate }}
        </template>
        <template v-slot:item.orderLinesSum="{ item }">
            {{ item.orderLinesSum | formatRub }}
        </template>
        <template v-slot:item.cashFlowsSum="{ item }">
            {{ item.cashFlowsSum | formatRub }}
        </template>
        <template v-slot:item.INSUM="{ item }">
            {{ item.INSUM | formatRub }}
        </template>
        <template v-slot:item.STATUS="{ item }">
            <div :class="statusColor(item.STATUS)">
                {{ orderStatus(item.STATUS) }}
            </div>
        </template>
    </v-data-table>
</template>

<script>
    import tableMixin from "../mixins/tableMixin";
    import utilsMixin from "../mixins/utilsMixin";
    import moment from "moment";
    import {mapGetters} from "vuex";

    export default {
        name: "Orders",
        mixins: [tableMixin, utilsMixin],
        data() {
            return {
                options: {
                    with: ['seller', 'employee'],
                    aggregateAttributes: [
                        'orderLinesCount', 'orderLinesSum', 'cashFlowsSum',
                    ],
                    filterAttributes: [
                        'INVOICE_DATA',
                        'INVOICE_NUM',
                        'seller.NAMEPOST',
                        'orderLinesSum',
                        'cashFlowsSum',
                        'STATUS',
                        'employee.FULLNAME',
                    ],
                    filterOperators: ['>=', 'CONTAIN', 'CONTAIN', '>=', '>=', 'IN', 'CONTAIN'],
                    filterValues: [moment().format('Y-MM-DD'), '', '', 0, 0, '0,1,2,3', ''],
                },
                datePicker: false,
                statuses: [
                    {text: 'В работе', value: '0,1,2,3'},
                    {text: 'Все', value: '0,1,2,3,4,5,6'},
                ],
                mobileFiltersVisible: false,
            }
        },
        computed: {
            ...mapGetters({
                orderStatus: 'ORDERSTATUS/GET'
            }),
            checkFilters() {
                return this.rules.isNumber(this.options.filterValues[3]) === true
                    && this.rules.required(this.options.filterValues[3]) === true
                    && this.rules.isNumber(this.options.filterValues[4]) === true
                    && this.rules.required(this.options.filterValues[4]) === true;
            }
        },
        methods: {
            statusColor(status) {
                if (status === 0) return 'red--text';
                if (status === 1) return;
                if (status === 2) return 'primary--text';
                if (status === 3) return 'info--text';
                return 'success--text';
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
                        text: 'Заказы',
                        to: {name: 'orders'},
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
