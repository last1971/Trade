<template>
    <invoice-lines-dependent v-model="options">
        <template v-slot:top>
            <v-form class="mx-2 mt-2">
                <v-row>
                    <v-text-field class="mx-2" label="Назвние" v-model="name"/>
                    <v-select :items="statuses"
                              label="Статус счета"
                              v-model="status"
                    />
                    <v-menu
                        :close-on-content-click="false"
                        :nudge-right="40"
                        class="mx-2"
                        min-width="290px"
                        offset-y
                        transition="scale-transition"
                        v-model="datePicker"
                    >
                        <template v-slot:activator="{ on }">
                            <v-text-field
                                :value="date | formatDate"
                                label="Счет позже"
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
                    <buyer-select :dense="true" :multiple="true" class="mx-2 mt-2" v-model="buyers"/>
                </v-row>
            </v-form>
        </template>
    </invoice-lines-dependent>
</template>

<script>
    import InvoiceLinesDependent from "./InvoiceLinesDependent";
    import InvoiceStatusSelect from "./InvoiceStatusSelect";
    import moment from "moment";
    import UserBuyers from "./UserBuyers";
    import BuyerSelect from "./BuyerSelect";
    import tableOptionsRouteMixin from "../mixins/tableOptionsRouteMixin";

    export default {
        name: "InvoiceLinesSearch",
        mixins: [tableOptionsRouteMixin],
        components: {BuyerSelect, InvoiceStatusSelect, InvoiceLinesDependent, UserBuyers},
        data() {
            return {
                options: {
                    with: ['category', 'good', 'name', 'invoice.buyer'],
                    aggregateAttributes: [
                        'reservesQuantity', 'pickUpsQuantity', 'transferOutLinesQuantity'
                    ],
                    filterAttributes: [],
                    filterOperators: [],
                    filterValues: [],
                },
                name: '',
                status: '',
                date: moment().format('Y-MM-DD'),
                buyers: [],
                statuses: [
                    {text: 'Все', value: ''},
                    {text: 'В работе', value: '0,1,2,3,4'},
                    {text: 'Закрыт', value: '5'},
                    {text: 'Без корзины', value: '0,1,2,3,4,5'},
                ],
                datePicker: false,
                model: 'INVOICE-LINE',
            }
        },
        watch: {
            name: _.debounce(function () {
                this.search('name.NAME', 'CONTAIN', this.name);
            }, 500),
            status: _.debounce(function () {
                this.search('invoice.STATUS', 'IN', this.status);
            }, 500),
            date: _.debounce(function () {
                this.search('invoice.DATA', '>', this.date);
            }, 500),
            buyers: _.debounce(function () {
                this.search('invoice.POKUPATCODE', 'IN', _.join(this.buyers));
            }, 500),
        },
        methods: {
            search(attr, oper, val) {
                let index = _.indexOf(this.options.filterAttributes, attr);
                if (index < 0) {
                    if (!_.isEmpty(val)) {
                        this.options.filterAttributes.push(attr);
                        this.options.filterOperators.push(oper);
                        this.options.filterValues.push(val);
                    }
                } else {
                    if (_.isEmpty(val)) {
                        this.options.filterAttributes.splice(index, 1)
                        this.options.filterOperators.splice(index, 1);
                        this.options.filterValues.splice(index, 1);
                    } else {
                        this.options.filterOperators.splice(index, 1, oper);
                        this.options.filterValues.splice(index, 1, val);
                    }
                }
            }
        },
        beforeRouteEnter(from, to, next) {
            next(vm => {

            })
        }
    }
</script>

<style scoped>

</style>
