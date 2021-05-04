<template>
    <v-data-table
        :headers="headers"
        :items="items"
        :loading="loading"
        :multi-sort="true"
        :must-sort="false"
        :options.sync="options"
        :server-items-length="total"
        loading-text="loadingText"
        :footer-props="{
            showFirstLastPage: true,
        }"
        :single-expand="true"
        show-expand
        :show-select="unpaid"
        v-model="selected"
        item-key="id"
    >
        <template v-slot:top>
            <v-card class="d-flex flex-row ma-2">
                <v-card-actions>
                    <payment-add @reload="updateItems" />
                    <v-btn :disabled="!canBeDeleted" rounded color="error"  @click="remove" class="ml-2">
                        <v-icon left>mdi-delete</v-icon>
                        УДАЛИТЬ
                    </v-btn>
                    <v-switch v-model="unpaid"
                              :label="unpaid ? 'Неоплаченные' : 'Все'"
                              class="ml-2"
                    />
                </v-card-actions>
            </v-card>
        </template>
        <template v-slot:body.prepend="{ isMobile }">
            <tr :class="{ 'v-data-table__mobile-table-row' : isMobile }"
                v-if="!isMobile || mobileFiltersVisible"
            >
                <td v-if="!isMobile"></td>
                <td v-if="!isMobile"></td>
                <td v-if="!isMobile && unpaid"></td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field label="Номер" v-model="options.filterValues[0]"/>
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
                                  v-model="options.filterValues[3]"
                    />
                </td>
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
                                :value="options.filterValues[4] | formatDate"
                                :label="'Раньше' + (isMobile ?  ' указанной Даты' : '')"
                                prepend-icon="mdi-calendar-edit"
                                readonly
                                v-on="on"
                            />
                        </template>
                        <v-date-picker @input="datePicker = false"
                                       first-day-of-week="1"
                                       v-model="options.filterValues[4]"
                        />
                    </v-menu>
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field label="Примечание содержит" v-model="options.filterValues[5]"/>
                </td>
                <td v-if="!isMobile"></td>
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
        <template v-slot:item.date="{ item }">
            <v-menu
                :close-on-content-click="false"
                :nudge-right="40"
                min-width="290px"
                offset-y
                transition="scale-transition"
                v-model="item.datePicker"
            >
                <template v-slot:activator="{ on }">
                    <v-text-field
                        :value="item.date | formatDate"
                        label="Дата"
                        prepend-icon="mdi-calendar-edit"
                        readonly
                        v-on="on"
                    />
                </template>
                <v-date-picker @input="save(item)" first-day-of-week="1" v-model="item.date" />
            </v-menu>
        </template>
        <template v-slot:item.number="{ item }">
            <edit-field @save="save" attribute="number" v-model="item"/>
        </template>
        <template v-slot:item.seller.NAMEPOST="{ item }">
            <v-edit-dialog>
                <slot name="cell">{{ item.seller.NAMEPOST }}</slot>
                <template v-slot:input>
                    <seller-select v-model="item.seller_id" @input="save(item)"/>
                </template>
            </v-edit-dialog>
        </template>
        <template v-slot:item.amount="{ item }">
            <edit-field :rules="[rules.isNumber, rules.required, rules.positive]"
                        @save="save"
                        attribute="amount"
                        v-model="item"
            >
                <template v-slot:cell>
                    {{ item.amount | formatRub }}
                </template>
            </edit-field>
        </template>
        <template v-slot:item.weight="{ item }">
            <edit-field :rules="[rules.isInteger, rules.required, rules.positive]"
                        @save="save"
                        attribute="weight"
                        v-model="item"
            />
        </template>
        <template v-slot:item.pay_before="{ item }">
            <v-menu
                :close-on-content-click="false"
                :nudge-right="40"
                min-width="290px"
                offset-y
                transition="scale-transition"
                v-model="item.payBeforePicker"
            >
                <template v-slot:activator="{ on }">
                    <v-text-field
                        :value="item.pay_before | formatDate"
                        label="Оплатить до"
                        prepend-icon="mdi-calendar-edit"
                        readonly
                        v-on="on"
                    />
                </template>
                <v-date-picker @input="save" first-day-of-week="1" v-model="item.pay_before" />
            </v-menu>
        </template>
        <template v-slot:item.comment="{ item }">
            <edit-field @save="save" attribute="comment" v-model="item"/>
        </template>
        <template v-slot:expanded-item="{ headers, item }">
            <td :colspan="headers.length" :key="item.REALPRICECODE">
                <v-card>
                    <payment-orders v-model="item" @reload="updateItems" class="my-4"/>
                </v-card>
            </td>
        </template>
    </v-data-table>
</template>

<script>


import tableMixin from "../../mixins/tableMixin";
import tableOptionsRouteMixin from "../../mixins/tableOptionsRouteMixin";
import utilsMixin from "../../mixins/utilsMixin";
import moment from 'moment';
import PaymentAdd from "./PaymentAdd";
import EditField from "../EditField";
import SellerSelect from "../seller/SellerSelect";
import PaymentOrders from "./PaymentOrders";

export default {
    name: "Payments",
    components: {PaymentOrders, SellerSelect, EditField, PaymentAdd},
    mixins: [tableMixin, tableOptionsRouteMixin, utilsMixin],
    data() {
        return {
            options: {
                with: ['seller', 'user'],
                aggregateAttributes: [
                    'paid',
                ],
                filterAttributes: [
                    'number',
                    'seller.NAMEPOST',
                    'amount',
                    'paid',
                    'pay_before',
                    'comment',
                    'amount',
                ],
                filterOperators: [
                    'LIKE', 'LIKE', '>=', '>=', '<=', 'LIKE', '>'
                ],
                filterValues: ['', '', 0, 0, moment().add(120, 'd').format('Y-MM-DD'), '', 'paid'],
            },
            model: 'PAYMENT',
            datePicker: false,
            mobileFiltersVisible: false,
            saving: false,
            selected: [],
        }
    },
    computed: {
        unpaid: {
            get() {
                return this.options.filterValues[6] === 'paid';
            },
            set(v) {
                const proxy = _.cloneDeep(this.options.filterValues);
                proxy[6] = v ? 'paid' : 0;
                this.$set(this.options, 'filterValues', proxy);
            }
        },
        canBeDeleted() {
            return this.selected.length;
        }
    },
    methods: {
        async save(item) {
            if (item.seller_id) {
                await this.$store.dispatch('PAYMENT/UPDATE', {item});
            }
        },
        async remove() {
            try {
                await Promise.all(this.selected.map((item) => {
                    return this.$store.dispatch('PAYMENT/REMOVE', item.id);
                }));
                this.selected = [];
                this.updateItems();
            } catch (e) {
                console.error(e);
            }
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
                    text: 'Платежи',
                    to: {name: 'payments'},
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
