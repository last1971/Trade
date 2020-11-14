<template>
    <v-data-table
        :headers="headers"
        :items="items"
        :loading="loading"
        :multi-sort="true"
        :options.sync="options"
        :server-items-length="total"
        item-key="DATATIME"
        :loading-text="loadingText"
        :hide-default-footer="true"
        :single-expand="true"
        show-expand
    >
        <template v-slot:top>
            <v-container>
                <v-row>
                    <v-col>
                        {{ count }} позиций на сумму {{ amount | formatRub }}
                    </v-col>
                </v-row>
            </v-container>
        </template>
        <template v-slot:body.prepend>
            <tr>
                <td>
                    <v-btn @click="updateItems" icon>
                        <v-icon>mdi-reload</v-icon>
                    </v-btn>
                </td>
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
                                :value="options.date | formatDate"
                                label="Продажи за"
                                prepend-icon="mdi-calendar-edit"
                                readonly
                                v-on="on"
                            />
                        </template>
                        <v-date-picker @input="datePicker = false"
                                       first-day-of-week="1"
                                       v-model="options.date"
                        />
                    </v-menu>
                </td>
                <td></td>
                <td>
                    <v-text-field :rules="[rules.required, rules.isNumber]"
                                  label="Сумма больше"
                                  reverse
                                  v-model="options.filterValues[0]"
                    />
                </td>
                <td>
                    <v-text-field label="Покупатель" v-model="options.filterValues[1]"/>
                </td>
                <td>
                    <v-text-field label="Продавец" v-model="options.filterValues[2]"/>
                </td>
            </tr>
        </template>
        <template v-slot:item.DATATIME="{ item }">
            {{ item.DATATIME | formatDateTime }}
        </template>
        <template v-slot:item.SUMMA="{ item }">
            {{ item.SUMMA | formatRub }}
        </template>
        <template v-slot:expanded-item="{ headers, item }">
            <td :colspan="headers.length" :key="item.DATATIME">
                <retail-sale-lines v-model="item"/>
            </td>
        </template>
    </v-data-table>
</template>

<script>
import   moment from 'moment';
import tableMixin from "../../mixins/tableMixin";
import utilsMixin from "../../mixins/utilsMixin";
import RetailSaleLines from "./RetailSaleLines";

export default {
    name: "RetailSales",
    components: {RetailSaleLines},
    mixins: [tableMixin, utilsMixin],
    data() {
        return {
            options: {
                filterAttributes: ['SUMMA', 'SHORTNAME', 'USERNAME'],
                filterOperators: ['>=', 'CONTAIN', 'CONTAIN'],
                filterValues: [0, '', ''],
                itemsPerPage: -1,
                date: moment().format('Y-MM-DD'),
            },
            model: 'RETAIL-SALE',
            datePicker: false,
        }
    },
    computed: {
        count() {
            return this.items.length;
        },
        amount() {
            return this.items.reduce((acc, item) => acc + parseFloat(item.SUMMA), 0);
        }
    }
}
</script>

<style scoped>

</style>
