<template>
    <v-data-table
        :headers="headers"
        :items="items"
        :loading="loading"
        loading-text="Ждем....."
        :hide-default-footer="true"
        item-key="id"
        :itemsPerPage="-1"
    >
        <template v-slot:top>
            <v-container>
                <v-row>
                    <v-col>
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
                                    label="Дата"
                                    prepend-icon="mdi-calendar-edit"
                                    readonly
                                    v-on="on"
                                />
                            </template>
                            <v-date-picker @input="datePicker = false" first-day-of-week="1" v-model="date" />
                        </v-menu>
                    </v-col>
                    <v-col>
                        <v-text-field :reverse="true"
                                     prefix="единиц валюты"
                                     suffix="за"
                                     type="number"
                                     v-model="amount"
                        />
                    </v-col>
                </v-row>
            </v-container>
        </template>
        <template v-slot:item.value="{ item }">
            <edit-field :value="{ value: item.value * amount, item }"
                        attribute="value"
                        @save="change"
                        :rules="[rules.isNumber, rules.positive]"
            >
                <template v-slot:cell>
                    {{ item.value * amount | formatRub }}
                </template>
            </edit-field>
        </template>
    </v-data-table>
</template>

<script>
import {mapGetters} from 'vuex';
import EditField from "./EditField";
import utilsMixin from "../mixins/utilsMixin";

export default {
    name: "ExchangeRates",
    components: {EditField},
    mixins: [utilsMixin],
    data() {
        return {
            headers : [
                {text: 'Код', value: 'CharCode', sortable: false},
                {text: 'Номер', value: 'currency.NumCode', sortable: false},
                {text: 'Название', value: 'currency.Name', sortable: false},
                {text: 'Получается', value: 'value', align: 'right', sortable: false},
            ],
            loading: false,
            datePicker: false,
            amount: 1,
            exchange: 1,
        }
    },
    computed: {
        ...mapGetters({
            items: 'EXCHANGE-RATE/ALL',
            exchangeDate: 'EXCHANGE-RATE/DATE',
        }),
        date: {
            get() {
                return this.exchangeDate;
            },
            set(date) {
                this.loading = true;
                this.$store.dispatch('EXCHANGE-RATE/SET', date)
                    .then(() => {})
                    .catch(() => {})
                    .then(() => this.loading = false);
            }
        }
    },
    methods: {
        change(value) {
            this.amount = value.value / value.item.value;
        }
    }
}
</script>

<style scoped>

</style>
