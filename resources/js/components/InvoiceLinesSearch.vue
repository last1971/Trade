<template>
    <invoice-lines-dependent v-model="options">
        <template v-slot:top>
            <v-form class="mx-2 mt-2">
                <v-row>
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
                                :value="options.filterValues[2] | formatDate"
                                label="Счет позже"
                                prepend-icon="mdi-calendar-edit"
                                readonly
                                v-on="on"
                                class="mx-2"
                            />
                        </template>
                        <v-date-picker @input="datePicker = false"
                                       first-day-of-week="1"
                                       v-model="options.filterValues[2]"
                        />
                    </v-menu>
                    <buyer-select :dense="true" :multiple="true" class="mx-2 mt-2" v-model="options.filterValues[3]"/>
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
    import CategorySelect from "./CategorySelect";

    export default {
        name: "InvoiceLinesSearch",
        mixins: [tableOptionsRouteMixin],
        components: {CategorySelect, BuyerSelect, InvoiceStatusSelect, InvoiceLinesDependent, UserBuyers},
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
