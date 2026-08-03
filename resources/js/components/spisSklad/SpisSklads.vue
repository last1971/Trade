<template>
    <v-data-table
        :headers="headers"
        :items="items"
        :loading="loading"
        :multi-sort="true"
        :options.sync="options"
        :server-items-length="total"
        loading-text="Loading... Please wait"
        :footer-props="{
            showFirstLastPage: true,
        }"
        item-key="SPISSKLADCODE"
    >
        <template v-slot:body.prepend="{ isMobile }">
            <tr :class="{ 'v-data-table__mobile-table-row' : isMobile }"
                v-if="!isMobile || mobileFiltersVisible"
            >
                <td v-if="!isMobile">
                    <div class="d-flex">
                        <v-btn @click="updateItems" icon>
                            <v-icon>mdi-reload</v-icon>
                        </v-btn>
                        <select-headers :model="model"/>
                    </div>
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <div class="d-flex align-center">
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
                                    :value="options.filterValues[0] ? $options.filters.formatDate(options.filterValues[0]) : ''"
                                    :label="'Позже' + (isMobile ?  ' указанной Даты' : '')"
                                    readonly
                                    clearable
                                    @click:clear="options.filterValues[0] = ''"
                                    v-on="on"
                                />
                            </template>
                            <v-date-picker @input="datePicker = false"
                                           first-day-of-week="1"
                                           v-model="options.filterValues[0]"
                            />
                        </v-menu>
                    </div>
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Название товара' : 'Содержит'"
                                  v-model="options.filterValues[1]"
                                  :filled="!!options.filterValues[1]"
                    />
                </td>
                <td v-if="!isMobile"></td>
                <td v-if="!isMobile"></td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Кто списал' : 'Содержит'"
                                  v-model="options.filterValues[2]"
                                  :filled="!!options.filterValues[2]"
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Причина' : 'Содержит'"
                                  v-model="options.filterValues[3]"
                                  :filled="!!options.filterValues[3]"
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Примечание' : 'Содержит'"
                                  v-model="options.filterValues[4]"
                                  :filled="!!options.filterValues[4]"
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
        <template v-slot:item.DATA="{ item }">
            {{ item.DATA | formatDate }}
        </template>
        <template v-slot:item.name.NAME="{ item }">
            <good-name :value="{ GOODSCODE: item.GOODSCODE, name: item.name }" :prim="false" v-if="item.name"/>
        </template>
        <template v-slot:item.PRICE="{ item }">
            {{ item.PRICE | formatRub }}
        </template>
    </v-data-table>
</template>

<script>
import tableMixin from "../../mixins/tableMixin";
import utilsMixin from "../../mixins/utilsMixin";
import tableOptionsRouteMixin from "../../mixins/tableOptionsRouteMixin";
import GoodName from "../good/GoodName";
import SelectHeaders from "../SelectHeaders";

export default {
    name: "SpisSklads",
    components: {GoodName, SelectHeaders},
    mixins: [tableMixin, tableOptionsRouteMixin, utilsMixin],
    data() {
        return {
            options: {
                with: ['name', 'reason'],
                filterAttributes: [
                    'DATA',
                    'name.NAME',
                    'USERNAME',
                    'reason.NAME',
                    'PRIM',
                ],
                filterOperators: [
                    '>=', 'CONTAIN', 'CONTAIN', 'CONTAIN', 'CONTAIN',
                ],
                filterValues: ['', '', '', '', ''],
                sortBy: ['SPISSKLADCODE'],
                sortDesc: [true],
            },
            model: 'SPIS-SKLAD',
            datePicker: false,
            mobileFiltersVisible: false,
        }
    },
    beforeRouteEnter(to, from, next) {
        next(vm => {
            // with — часть кода, не настройка: игнорируем протухший список из URL/localStorage
            vm.options.with = ['name', 'reason'];
            vm.$store.commit('BREADCRUMBS/SET', [
                {
                    text: 'Торговля',
                    to: {name: 'home'},
                    exact: true,
                    disabled: false,
                },
                {
                    text: 'Списания',
                    to: {name: 'spis-sklads'},
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
