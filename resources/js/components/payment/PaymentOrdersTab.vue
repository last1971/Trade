<template>
    <v-data-table
        :headers="headers"
        :items="items"
        :loading="loading"
        :multi-sort="true"
        :options.sync="options"
        :server-items-length="total"
        :loading-text="loadingText"
        item-key="id"
    >
        <template v-slot:body.prepend="{ isMobile }">
            <tr :class="{ 'v-data-table__mobile-table-row' : isMobile }"
                v-if="!isMobile || mobileFiltersVisible"
            >
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <date-picker v-model="options.filterValues[0]" :is-mobile="isMobile"/>
                </td>
                <td v-if="!isMobile"></td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :rules="[rules.required, rules.isNumber]"
                                  :label="isMobile ? 'Сумма больше' : 'Больше'"
                                  reverse
                                  v-model="options.filterValues[1]"
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <seller-select v-model="options.filterValues[2]" :multiple="true"/>
                </td>
                <td v-if="!isMobile"></td>
                <td v-if="!isMobile"></td>
                <td v-if="!isMobile"></td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field label="Содержит" v-model="options.filterValues[3]" />
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
        <template v-slot:item.date="{ item }">
            {{ item.date | formatDate }}
        </template>
        <template v-slot:item.amount="{ item }">
            <span>
            {{ item.amount | formatRub }}
                </span>
        </template>
        <template v-slot:item.payment.date="{ item }">
            {{ item.date | formatDate }}
        </template>
        <template v-slot:item.payment.amount="{ item }">
            {{ item.payment.amount | formatRub }}
        </template>
    </v-data-table>
</template>

<script>

import tableMixin from "../../mixins/tableMixin";
import utilsMixin from "../../mixins/utilsMixin";
import DatePicker from "../DatePicker";
import SellerSelect from "../SellerSelect";

export default {
    name: "PaymentOrdersTab",
    components: {SellerSelect, DatePicker},
    mixins: [tableMixin, utilsMixin],
    data() {
        return {
            options: {
                with: ['payment.seller'],
                filterAttributes: [
                    'date', 'amount', 'payment.seller_id', 'payment.comment'
                ],
                filterOperators: ['>', '>', 'IN', 'LIKE'],
                filterValues: ['2010-01-01', 0, [], ''],
                sortBy: ['date'],
                sortDesc: [false],
              //  itemsPerPage: -1,
            },
            mobileFiltersVisible: false,
            dependent: true,
            model: 'PAYMENT-ORDER',
            datePicker: false,
        }
    },

}
</script>

<style scoped>

</style>
