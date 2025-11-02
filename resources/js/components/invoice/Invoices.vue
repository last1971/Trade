<template>
    <v-data-table
        :headers="headers2"
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
                    <v-speed-dial :open-on-hover="true" direction="right">
                        <template v-slot:activator>
                            <v-btn icon>
                                <v-icon>mdi-hand-pointing-right</v-icon>
                            </v-btn>
                        </template>
                        <v-btn @click="updateItems" fab>
                            <v-icon>mdi-reload</v-icon>
                        </v-btn>
                        <v-btn :disabled="!$store.getters['AUTH/HAS_PERMISSION']('invoice.xlsx')"
                               :loading="saving"
                               @click="saveFile"
                               fab
                        >
                            <v-icon color="green">mdi-microsoft-excel</v-icon>
                        </v-btn>
                        <v-btn :to="{ name: 'invoice', params: { id: 0 }}"
                               :disabled="!$store.getters['AUTH/HAS_PERMISSION']('invoice.store')"
                               fab
                        >
                            <v-icon color="green">mdi-plus</v-icon>
                        </v-btn>
                    </v-speed-dial>
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
                                  :filled="!!options.filterValues[1]"
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Заявка' : 'Равна'"
                                  :rules="[rules.isInteger]"
                                  v-model="options.filterValues[11]"
                                  :filled="!!options.filterValues[11]"
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Название покупателя' : 'Начинается с'"
                                  v-model="options.filterValues[4]"
                                  :filled="!!options.filterValues[4]"
                    />
                </td>
                <td v-if="!isMobile"></td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :rules="[rules.required, rules.isNumber]"
                                  :label="isMobile ? 'Сумма больше' : 'Больше'"
                                  reverse
                                  v-model="options.filterValues[2]"
                                  :filled="options.filterValues[2] > 0"
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :rules="[rules.required, rules.isNumber]"
                                  :label="isMobile ? 'Оплата больше' : 'Больше'"
                                  reverse
                                  v-model="options.filterValues[7]"
                                  :filled="options.filterValues[7] > 0"
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :rules="[rules.required, rules.isNumber]"
                                  :label="isMobile ? 'Отгрузка больше' : 'Больше'"
                                  reverse
                                  v-model="options.filterValues[8]"
                                  :filled="options.filterValues[8] > 0"
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
                                  :filled="!!options.filterValues[6]"
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Фамилия манагера' : 'Начинается с'"
                                  v-model="options.filterValues[5]"
                                  :filled="!!options.filterValues[5]"
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }" v-if="hasPermission">
                    <v-text-field :label="isMobile ? 'Примечание' : 'Содержит'"
                                  v-model="options.filterValues[9]"
                                  :filled="!!options.filterValues[9]"
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }" v-if="hasPermission">
                    <v-text-field :label="isMobile ? 'ИГК' : 'Содержит'"
                                  v-model="options.filterValues[10]"
                                  :filled="!!options.filterValues[10]"
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
        <template v-slot:item.actions="{ item }">
            <invoice-pdf v-model="item"/>
        </template>
        <template v-slot:item.NS="{ item }">
            <router-link :to="{ name: 'invoice', params: { id: item.SCODE } }">
                {{ item.NS }}
            </router-link>
        </template>
        <template v-slot:item.DATA="{ item }">
            {{ item.DATA | formatDate }}
        </template>
        <template v-slot:item.invoiceLinesSum="{ item }">
            {{ item.invoiceLinesSum | formatRub }}
        </template>
        <template v-slot:item.cashFlowsSum="{ item }">
            <cash-flows-modal v-model="item"
                              :text="item.cashFlowsSum"
                              :text-class="compareToColorText(item.invoiceLinesSum, item.cashFlowsSum)"
            />
        </template>
        <template v-slot:item.transferOutLinesSum="{ item }">
            <div :class="compareToColorText(item.invoiceLinesSum, item.transferOutLinesSum)">
                {{ item.transferOutLinesSum | formatRub }}
            </div>
        </template>
        <template v-slot:item.STATUS="{ item }">
            <invoice-status-select-inline @save="save" attribute="STATUS" v-model="item">
                <template v-slot:cell>
                    {{ invoiceStatus(item.STATUS) }}
                </template>
            </invoice-status-select-inline>
        </template>
        <template v-slot:item.PRIM="{ item }">
            <edit-field
                @save="save"
                attribute="PRIM"
                v-model="item"
            />
        </template>
        <template v-slot:item.IGK="{ item }">
            <edit-field
                @save="save"
                attribute="IGK"
                v-model="item"
                :additional-text="pickUpTime(item)"
            />
        </template>
    </v-data-table>
</template>

<script>
import moment from 'moment';
import {mapGetters} from 'vuex';
import tableMixin from "../../mixins/tableMixin";
import utilsMixin from "../../mixins/utilsMixin";
import tableOptionsRouteMixin from "../../mixins/tableOptionsRouteMixin";
import InvoicePdf from "./InvoicePdf";
import EditField from "../EditField";
import InvoiceStatusSelect from "./InvoiceStatusSelect";
import InvoiceStatusSelectInline from "./InvoiceStatusSelectInline";
import CashFlowsModal from "../CashFlowsModal.vue";

export default {
    name: "Invoices",
    components: {CashFlowsModal, InvoiceStatusSelect, EditField, InvoicePdf, InvoiceStatusSelectInline},
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
                    'S.PRIM',
                    'IGK',
                    'NZ'
                ],
                filterOperators: [
                    '>=', 'LIKE', '>=', 'IN', 'CONTAIN', 'CONTAIN', 'CONTAIN', '>=', '>=', 'CONTAIN', 'CONTAIN', '='
                ],
                filterValues: [moment().format('Y-MM-DD'), '', 0, [], '', '', '', 0, 0, '', '', ''],
                sortDesc: [],
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
        },
        headers2() {
            return this.hasPermission
                ? this.headers : _.initial(this.headers);
        },
        hasPermission() {
            return this.$store.getters['AUTH/HAS_PERMISSION']('invoice.update');
        },
    },
    methods: {
        previousItem(item) {
            const index = this.items.indexOf(item);
            if (index > 0) return this.items[index - 1].SCODE;
            return _.last(this.items).SCODE;
        },
        compareToColorText(a, b) {
            if (parseFloat(a) > parseFloat(b) && parseFloat(b) === 0) return 'red--text';
            if (parseFloat(a) === parseFloat(b)) return 'green--text';
            return 'primary--text';
        },
        saveFile() {
            this.saving = true;
            this.$store.dispatch('INVOICE/SAVE', this.options)
                .then(() => {
                })
                .catch(() => {
                })
                .then(() => this.saving = false);
        },
        pickUpTime(item) {
            return item.FINISH_PICKUP && item.START_PICKUP
                ? 'Начали: ' + item.START_PICKUP + ', Закончили: ' + item.FINISH_PICKUP
                : null;
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
