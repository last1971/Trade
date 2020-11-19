<template>
    <v-data-table
        :headers="headers"
        :hide-default-footer="true"
        :items="items"
        :loading="loading"
        :multi-sort="true"
        :options.sync="options"
        :server-items-length="total"
        item-key="BACKSHOPCODE"
        :loading-text="loadingText"
    >
        <template v-slot:body.prepend>
            <tr>
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
                                :value="date | formatDate"
                                label="Возвраты за"
                                prepend-icon="mdi-calendar-edit"
                                readonly
                                v-on="on"
                            />
                        </template>
                        <v-date-picker @input="datePicker = false"
                                       first-day-of-week="1"
                                       v-model="date"
                        />
                    </v-menu>
                </td>
            </tr>
        </template>
        <template v-slot:item.DATATIMEBACK="{ item }">
            {{ item.DATATIMEBACK | formatDateTime }}
        </template>
        <template v-slot:item.PRICE="{ item }">
            {{ item.AMOUNT | formatRub }}
        </template>
        <template v-slot:item.AMOUNT="{ item }">
            {{ item.AMOUNT | formatRub }}
        </template>
        <template v-slot:expanded-item="{ headers, item }">
            <td :colspan="headers.length" :key="item.DATATIME">
                <retail-sale-lines v-model="item"/>
            </td>
        </template>
        <template v-slot:item.DATATIMESALE="{ item }">
            {{ item.DATATIMESALE | formatDateTime }}
        </template>
    </v-data-table>
</template>

<script>
import tableMixin from "../../mixins/tableMixin";
import moment from "moment";

export default {
    name: "ReatilStoreReturns",
    mixins: [tableMixin],
    data() {
        return {
            options: {
                with: ['good.name', 'good.category'],
                filterAttributes: ['DATATIMEBACK'],
                filterOperators: ['BETWEENDATE'],
                filterValues: [
                    [
                        moment().format('Y-MM-DD'),
                        moment().add(1, 'days').format('Y-MM-DD')
                    ]
                ],
                itemsPerPage: -1,
            },
            model: 'RETAIL-STORE-RETURN',
            dependent: true,
            date: moment().format('Y-MM-DD'),
            datePicker: false,
        }
    },
    watch: {
        date(val) {
            this.options.filterValues = [
                [
                    moment(val).format('Y-MM-DD'),
                    moment(val).add(1, 'days').format('Y-MM-DD')
                ]
            ]
        }
    }
}
</script>

<style scoped>

</style>
