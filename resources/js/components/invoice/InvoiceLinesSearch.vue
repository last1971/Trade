<template>
    <invoice-lines-dependent v-model="options">
        <template v-slot:top>
            <v-form class="mx-2 mt-2 d-inline-flex">

                    <category-select :dense="true"
                                     :multiple="true"
                                     class="mx-2 mt-2"
                                     v-model="options.filterValues[4]"
                    />
                    <v-text-field class="mx-2" label="Назвние" v-model="options.filterValues[0]"/>
                    <v-select :items="statuses"
                              label="Статус счета"
                              class="mx-2"
                              v-model="options.filterValues[1]"
                    />
                    <date-picker v-model="options.filterValues[2]"/>
                    <buyer-select :dense="true" :multiple="true" class="mx-2 mt-2" v-model="options.filterValues[3]"/>
                    <v-btn :loading="saving" @click="save" class="mx-2" fab icon>
                        <v-icon color="green">mdi-microsoft-excel</v-icon>
                    </v-btn>

            </v-form>
        </template>
    </invoice-lines-dependent>
</template>

<script>
    import InvoiceLinesDependent from "./InvoiceLinesDependent";
    import InvoiceStatusSelect from "./InvoiceStatusSelect";
    import moment from "moment";
    import UserBuyers from "../UserBuyers";
    import BuyerSelect from "../BuyerSelect";
    import tableOptionsRouteMixin from "../../mixins/tableOptionsRouteMixin";
    import CategorySelect from "../CategorySelect";
    import DatePicker from "../DatePicker";

    export default {
        name: "InvoiceLinesSearch",
        mixins: [tableOptionsRouteMixin],
        components: {DatePicker, CategorySelect, BuyerSelect, InvoiceStatusSelect, InvoiceLinesDependent, UserBuyers},
        data() {
            return {
                options: {
                    with: ['category', 'good', 'name', 'invoice.buyer'],
                    aggregateAttributes: [
                        'reservesQuantity', 'pickUpsQuantity', 'transferOutLinesQuantity'
                    ],
                    filterAttributes: [
                        'name.NAME', 'invoice.STATUS', 'invoice.DATA', 'invoice.POKUPATCODE', 'category.CATEGORYCODE'
                    ],
                    filterOperators: ['CONTAIN', 'IN', '>=', 'IN', 'IN'],
                    filterValues: ['', [], moment().format('Y-MM-DD'), [], []],
                },
                statuses: [
                    {text: 'Все', value: []},
                    {text: 'В работе', value: [0, 1, 2, 3, 4]},
                    {text: 'Закрыт', value: [5]},
                    {text: 'Без корзины', value: [0, 1, 2, 3, 4, 5]},
                ],
                datePicker: false,
                model: 'INVOICE-LINE',
                saving: false,
            }
        },
        methods: {
            save() {
                this.saving = true;
                this.$store.dispatch('INVOICE-LINE/SAVE', this.options)
                    .then(() => {
                    })
                    .catch(() => {
                    })
                    .then(() => this.saving = false);
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
                        text: 'Поиск в Счетах',
                        to: {name: 'invoice-lines'},
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
