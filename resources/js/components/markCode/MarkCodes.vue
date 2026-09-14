<template>
    <div>
        <!-- Поле скана над таблицей: код в руках чаще, чем фильтр в голове. -->
        <mark-code-scan class="mb-3"/>
        <v-data-table
            :headers="headers"
            :items="items"
            :loading="loading"
            :multi-sort="false"
            :options.sync="options"
            :server-items-length="total"
            loading-text="Loading... Please wait"
            :footer-props="{
                showFirstLastPage: true,
            }"
            item-key="MARKCODE"
        >
            <template v-slot:body.prepend="{ isMobile }">
                <mark-code-filter-row :headers="headers"
                                      :options="options"
                                      :model="model"
                                      :is-mobile="isMobile"
                                      v-if="!isMobile || mobileFiltersVisible"
                                      @reload="updateItems"
                />
                <tr v-if="isMobile">
                    <td>
                        <v-btn @click="mobileFiltersVisible=true" block v-if="!mobileFiltersVisible">
                            Показать фильтры
                        </v-btn>
                        <v-btn @click="mobileFiltersVisible=false" block v-else>Скрыть фильтры</v-btn>
                    </td>
                </tr>
            </template>
            <template v-slot:item.CREATED_AT="{ item }">
                {{ item.CREATED_AT | formatDate }}
            </template>
            <template v-slot:item.KI="{ item }">
                <!-- Код ведёт в свою карточку: там наши данные и живой ответ ГИС МТ -->
                <router-link class="ki-code" :to="{ name: 'mark-code', params: { id: item.MARKCODE } }">
                    {{ item.KI }}
                </router-link>
                <v-icon x-small class="copy-ico ml-1" title="Скопировать код"
                        @click.stop="copy(item.KI)">mdi-content-copy</v-icon>
            </template>
            <template v-slot:item.good.name.NAME="{ item }">
                <good-name :value="item.good" :prim="false" v-if="item.good"/>
            </template>
            <template v-slot:item.STATUS="{ item }">
                {{ statusText(item.STATUS) }}
            </template>
            <template v-slot:item.TRANSFER_TYPE="{ item }">
                {{ transferText(item.TRANSFER_TYPE) }}
            </template>
            <template v-slot:item.invoiceLine.invoice.NS="{ item }">
                <span v-if="item.invoiceLine && item.invoiceLine.invoice">
                    <router-link :to="{ name: 'invoice', params: { id: item.invoiceLine.invoice.SCODE } }">
                        №{{ item.invoiceLine.invoice.NS }} от {{ item.invoiceLine.invoice.DATA | formatDate }}
                    </router-link>
                    <div v-if="item.invoiceLine.invoice.buyer">
                        {{ item.invoiceLine.invoice.buyer.SHORTNAME }}
                    </div>
                </span>
            </template>
            <template v-slot:item.transferOutLine.transferOut.NSF="{ item }">
                <span v-if="item.transferOutLine && item.transferOutLine.transferOut">
                    <router-link :to="{ name: 'transfer-out', params: { id: item.transferOutLine.transferOut.SFCODE } }">
                        №{{ item.transferOutLine.transferOut.NSF }} от {{ item.transferOutLine.transferOut.DATA | formatDate }}
                    </router-link>
                    <div v-if="item.transferOutLine.transferOut.buyer">
                        {{ item.transferOutLine.transferOut.buyer.SHORTNAME }}
                    </div>
                </span>
            </template>
            <template v-slot:item.storeLine.NP="{ item }">
                <span v-if="item.storeLine">
                    <router-link :to="storeInLink(item.storeLine.NP)">
                        №{{ item.storeLine.NP }} от {{ item.storeLine.DATA | formatDate }}
                    </router-link>
                    <div v-if="item.storeLine.entry && item.storeLine.entry.seller">
                        {{ item.storeLine.entry.seller.NAMEPOST }}
                    </div>
                </span>
            </template>
            <template v-slot:item.storeLine.orderLine.MASTER_ID="{ item }">
                <span v-if="item.storeLine && item.storeLine.orderLine">
                    <router-link :to="{ name: 'order', params: { id: item.storeLine.orderLine.MASTER_ID } }">
                        №{{ item.storeLine.orderLine.MASTER_ID }}<template v-if="item.storeLine.orderLine.order"> от {{ item.storeLine.orderLine.order.DATA_ZAK | formatDate }}</template>
                    </router-link>
                    <div v-if="item.storeLine.orderLine.order && item.storeLine.orderLine.order.seller">
                        {{ item.storeLine.orderLine.order.seller.NAMEPOST }}
                    </div>
                </span>
            </template>
            <template v-slot:item.spisSklad.DATA="{ item }">
                <span v-if="item.spisSklad">
                    {{ item.spisSklad.DATA | formatDate }}
                    <template v-if="item.spisSklad.reason">— {{ item.spisSklad.reason.NAME }}</template>
                </span>
            </template>
        </v-data-table>
    </div>
</template>

<script>
import tableMixin from "../../mixins/tableMixin";
import utilsMixin from "../../mixins/utilsMixin";
import tableOptionsRouteMixin from "../../mixins/tableOptionsRouteMixin";
import markCodeTableMixin, {STATUS_CODES, TRANSFER_CODES} from "../../mixins/markCodeTableMixin";
import GoodName from "../good/GoodName";
import MarkCodeFilterRow from "./MarkCodeFilterRow";
import MarkCodeScan from "./MarkCodeScan";

export default {
    name: "MarkCodes",
    components: {GoodName, MarkCodeFilterRow, MarkCodeScan},
    mixins: [tableMixin, tableOptionsRouteMixin, utilsMixin, markCodeTableMixin],
    data() {
        return {
            options: {
                with: [
                    'good.name',
                    'invoiceLine.invoice.buyer',
                    'transferOutLine.transferOut.buyer',
                    'storeLine.orderLine.order.seller',
                    'storeLine.entry.seller',
                    'spisSklad.reason',
                ],
                filterAttributes: [
                    'CREATED_AT',
                    'KI',
                    'name.NAME',
                    'GTIN',
                    'SERIAL_NUMBER',
                    'STATUS',
                    'TRANSFER_TYPE',
                    'invoice.NS',
                ],
                filterOperators: [
                    '>=', 'CONTAIN', 'CONTAIN', 'CONTAIN', 'CONTAIN', 'IN', 'IN', '=',
                ],
                filterValues: ['', '', '', '', '', [], [], ''],
                sortBy: ['MARKCODE'],
                sortDesc: [true],
            },
            model: 'MARK-CODE',
            mobileFiltersVisible: false,
        }
    },
    methods: {
        // Копирование в буфер; на http navigator.clipboard недоступен — фолбэк.
        copy(text) {
            const value = String(text);
            const done = () => this.$store.commit('SNACKBAR/PUSH',
                {text: 'Скопировано: ' + value, color: 'success', status: true}, {root: true});
            if (navigator.clipboard) {
                navigator.clipboard.writeText(value).then(done).catch(() => this.copyFallback(value, done));
            } else {
                this.copyFallback(value, done);
            }
        },
        copyFallback(value, done) {
            const el = document.createElement('textarea');
            el.value = value;
            el.style.position = 'fixed';
            el.style.opacity = '0';
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
            done();
        },
    },
    beforeRouteEnter(to, from, next) {
        next(vm => {
            // with — часть кода, а не настройка: не даём протухшему списку из URL/localStorage
            // отключить подгрузку связей (контрагенты, даты документов)
            vm.options.with = [
                'good.name',
                'invoiceLine.invoice.buyer',
                'transferOutLine.transferOut.buyer',
                'storeLine.orderLine.order.seller',
                'storeLine.entry.seller',
                'spisSklad.reason',
            ];
            // Статус и вид передачи приходят из URL или localStorage: незнакомое
            // значение (в том числе осевший там ноль) молча прячет все марки.
            [['STATUS', STATUS_CODES], ['TRANSFER_TYPE', TRANSFER_CODES]].forEach(([attr, codes]) => {
                const index = vm.options.filterAttributes.indexOf(attr);
                if (index < 0) return;
                const value = vm.options.filterValues[index];
                vm.$set(vm.options.filterValues, index,
                    (Array.isArray(value) ? value : []).filter((code) => codes.includes(code)));
            });
            vm.$store.commit('BREADCRUMBS/SET', [
                {
                    text: 'Торговля',
                    to: {name: 'home'},
                    exact: true,
                    disabled: false,
                },
                {
                    text: 'Марки ЧЗ',
                    to: {name: 'mark-codes'},
                    exact: true,
                    disabled: true,
                }
            ]);
        });
    }
}
</script>

<style scoped>
.ki-code {
    font-family: monospace;
    font-size: 12px;
    word-break: break-all;
}

.copy-ico {
    cursor: pointer;
    opacity: .5;
}

.copy-ico:hover {
    opacity: 1;
}
</style>
