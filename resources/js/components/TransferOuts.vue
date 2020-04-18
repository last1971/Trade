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
        item-key="SFCODE"
        loading-text="Loading... Please wait"
    >
        <template v-slot:body.prepend="{ isMobile }">
            <tr :class="{ 'v-data-table__mobile-table-row' : isMobile }"
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
                    <v-text-field :label="isMobile ? 'Номер' : 'Равен'"
                                  :rules="[rules.isInteger]"
                                  v-model="options.filterValues[1]"
                    />
                </td>
                <td v-if="!isMobile"></td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Название покупателя' : 'Начинается с'"
                                  v-model="options.filterValues[2]"
                    />
                </td>
                <td v-if="!isMobile"></td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Сумма больше' : 'Больше'"
                                  :rules="[rules.required, rules.isNumber]"
                                  reverse
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
        <template v-slot:item.NSF="{ item }">
            <router-link :to="{ name: 'transfer-out', params: { id: item.SFCODE } }">
                <v-tooltip top>
                    <template v-slot:activator="{ on }">
                        <span v-on="on">{{ item.NSF }}</span>
                    </template>
                    <span>{{ item.PRIM || 'перейти'}}</span>
                </v-tooltip>
            </router-link>
        </template>
        <template v-slot:item.invoice="{ item }">
            <router-link :to="{ name: 'invoice', params: { id: item.SCODE } }">
                <v-tooltip top>
                    <template v-slot:activator="{ on }">
                        <span v-on="on">
                           № {{ item.invoice.NS }} от {{ item.invoice.DATA | formatDate }}
                        </span>
                    </template>
                    <span>{{ item.invoice.PRIM || 'перейти'}}</span>
                </v-tooltip>
            </router-link>
        </template>
        <template v-slot:item.DATA="{ item }">
            {{ item.DATA | formatDate }}
        </template>
        <template v-slot:item.transferOutLinesSum="{ item }">
            {{ item.transferOutLinesSum | formatRub }}
        </template>
    </v-data-table>
</template>

<script>
    import moment from 'moment';
    import tableMixin from "../mixins/tableMixin";
    import utilsMixin from "../mixins/utilsMixin";
    import {mapGetters} from "vuex";

    export default {
        name: "TransferOuts",
        mixins: [tableMixin, utilsMixin],
        data() {
            return {
                options: {
                    with: ['buyer', 'employee', 'firm', 'invoice'],
                    aggregateAttributes: [
                        'transferOutLinesCount', 'transferOutLinesSum'
                    ],
                    filterAttributes: [
                        'DATA',
                        'NSF',
                        'buyer.SHORTNAME',
                        'transferOutLinesSum',
                        'firm.FIRMNAME',
                        'employee.FULLNAME',
                    ],
                    filterOperators: ['>=', 'LIKE', 'CONTAIN', '>=', 'CONTAIN', 'CONTAIN'],
                    filterValues: [moment().format('Y-MM-DD'), '', '', 0, '', ''],
                },
                datePicker: false,
                mobileFiltersVisible: false,
            }
        },
        computed: {
            ...mapGetters({
                invoiceStatus: 'INVOICESTATUS/GET'
            }),
            checkFilters() {
                return this.rules.isNumber(this.options.filterValues[3]) === true
                    && this.rules.required(this.options.filterValues[3]) === true;
            },
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
                        text: 'Исх.УПД',
                        to: {name: 'transfer-outs'},
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
