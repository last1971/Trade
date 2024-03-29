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
                <td v-if="!isMobile">
                    <v-speed-dial :open-on-hover="true" direction="right">
                        <template v-slot:activator>
                            <v-btn icon>
                                <v-icon>mdi-hand-pointing-right</v-icon>
                            </v-btn>
                        </template>
                        <v-btn @click="updateItems" fab>
                            <v-icon>mdi-reload</v-icon>
                        </v-btn>
                        <v-btn :to="{ name: 'order', params: { id: 0 }}" fab>
                            <v-icon color="green">mdi-plus</v-icon>
                        </v-btn>
                    </v-speed-dial>
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <date-picker v-model="options.filterValues[0]" :is-mobile="isMobile"/>
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Номер' : 'Содержит'"
                                  :rules="[rules.isInteger]"
                                  v-model="options.filterValues[1]"
                                  :filled="!!options.filterValues[1]"
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Поставщик' : 'Начинается с'"
                                  v-model="options.filterValues[2]"
                                  :filled="!!options.filterValues[2]"
                    />
                </td>
                <td v-if="!isMobile"></td>
                <td v-if="!isMobile"></td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Сумма больше' : 'Больше'"
                                  :rules="[rules.required, rules.isNumber]"
                                  reverse
                                  v-model="options.filterValues[3]"
                                  :filled="options.filterValues[3] > 0"
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Оплата больше' : 'Больше'"
                                  :rules="[rules.required, rules.isNumber]"
                                  reverse
                                  v-model="options.filterValues[4]"
                                  :filled="options.filterValues[4] > 0"
                    />
                </td>
                <td v-if="!isMobile"></td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-select :items="statuses"
                              :label="isMobile ? 'Статус' : 'Равен'"
                              v-model="options.filterValues[5]"
                              :filled="options.filterValues[5].length > 0"
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Фамилия манагера' : 'Начинается с'"
                                  v-model="options.filterValues[6]"
                                  :filled="!!options.filterValues[6]"
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
            <router-link :to="{ name: 'order', params: { id: item.ID } }">
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
            <order-status-select-inline @save="save" attribute="STATUS" v-model="item">
                <template v-slot:cell>
                    <div :class="statusColor(item.STATUS)">
                        {{ orderStatus(item.STATUS) }}
                    </div>
                </template>
            </order-status-select-inline>
        </template>
    </v-data-table>
</template>

<script>
import tableMixin from "../../mixins/tableMixin";
import utilsMixin from "../../mixins/utilsMixin";
import moment from "moment";
import {mapGetters} from "vuex";
import tableOptionsRouteMixin from "../../mixins/tableOptionsRouteMixin";
import OrderStatusSelectInline from "./OrderStatusSelectInline";
import DatePicker from "../DatePicker";

export default {
    name: "Orders",
    components: {DatePicker, OrderStatusSelectInline},
    mixins: [tableMixin, tableOptionsRouteMixin, utilsMixin],
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
                    filterValues: [moment().format('Y-MM-DD'), '', '', 0, 0, [0, 1, 2, 3], ''],
                },
                datePicker: false,
                statuses: [
                    {text: 'В работе', value: [0, 1, 2, 3]},
                    {text: 'В пути', value: [2, 3]},
                    {text: 'Все', value: []},
                ],
                mobileFiltersVisible: false,
                model: 'ORDER',
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
