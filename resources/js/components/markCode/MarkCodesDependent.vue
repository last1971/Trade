<template>
    <div>
        <div class="d-flex align-center flex-wrap mx-2 pt-2">
            <v-btn small :outlined="!inventory" :color="inventory ? 'primary' : ''" @click="toggleInventory">
                <v-icon small left>mdi-barcode-scan</v-icon>
                Инвентаризация
            </v-btn>
            <template v-if="inventory">
                <v-text-field
                    ref="scanField"
                    v-model="scanInput"
                    label="Сканируйте код"
                    class="ml-4"
                    style="max-width: 320px"
                    dense
                    hide-details
                    autofocus
                    clearable
                    @keyup.enter="onScan"
                />
                <v-switch
                    v-model="hideScanned"
                    label="Скрывать отсканированные"
                    class="ml-4 mt-0"
                    dense
                    hide-details
                />
                <span class="ml-4">
                    Отсканировано <b>{{ scannedKis.length }}</b> из <b>{{ items.length }}</b> не выбывших
                </span>
                <v-btn icon small class="ml-2" title="Сбросить инвентаризацию" @click="resetInventory">
                    <v-icon>mdi-restart</v-icon>
                </v-btn>
            </template>
            <v-spacer/>
            <select-headers :model="model"/>
        </div>
        <v-alert v-if="extraKis.length" type="error" dense text class="mx-2 my-1">
            Лишние коды — отсканированы, но в списке их нет:
            <div v-for="ki in extraKis" :key="ki" class="ki-code">{{ ki }}</div>
        </v-alert>
        <v-data-table
            :headers="mutatedHeaders"
            :items="visibleItems"
            :loading="loading"
            :multi-sort="false"
            :options.sync="options"
            :server-items-length="total"
            :loading-text="loadingText"
            :footer-props="{
                showFirstLastPage: true,
            }"
            :hide-default-footer="inventory"
            item-key="MARKCODE"
            :item-class="rowClass"
            class="mx-2"
        >
            <template v-slot:body.prepend="{ isMobile }">
                <mark-code-filter-row :headers="mutatedHeaders"
                                      :options="options"
                                      :is-mobile="isMobile"
                                      v-if="!isMobile"
                                      @reload="updateItems"
                />
            </template>
            <template v-slot:item.CREATED_AT="{ item }">
                {{ item.CREATED_AT | formatDate }}
            </template>
            <template v-slot:item.KI="{ item }">
                <span class="ki-code">{{ item.KI }}</span>
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
import markCodeTableMixin from "../../mixins/markCodeTableMixin";
import MarkCodeFilterRow from "./MarkCodeFilterRow";
import SelectHeaders from "../SelectHeaders";
import {extractKi} from "../../helpers/markScan";

export default {
    name: "MarkCodesDependent",
    components: {MarkCodeFilterRow, SelectHeaders},
    mixins: [tableMixin, markCodeTableMixin],
    props: {
        value: {
            type: Number,
            required: true,
        },
    },
    data() {
        return {
            options: {
                with: [
                    'invoiceLine.invoice.buyer',
                    'transferOutLine.transferOut.buyer',
                    'storeLine.orderLine.order.seller',
                    'storeLine.entry.seller',
                    'spisSklad.reason',
                ],
                filterAttributes: [
                    'GOODSCODE',
                    'CREATED_AT',
                    'KI',
                    'STATUS',
                    'TRANSFER_TYPE',
                    'invoice.NS',
                ],
                filterOperators: ['=', '>=', 'CONTAIN', 'IN', 'IN', '='],
                filterValues: [this.value, '', '', [], [], ''],
                sortBy: ['MARKCODE'],
                sortDesc: [true],
                itemsPerPage: 15,
                page: 1,
            },
            dependent: true,
            model: 'MARK-CODE',
            // GTIN у всех марок товара один, серийник дословно виден внутри кода — в карточке товара это шум
            removeHeaders: ['actions', 'good.name.NAME', 'GTIN', 'SERIAL_NUMBER'],
            inventory: false,
            scanInput: '',
            scannedKis: [],
            extraKis: [],
            hideScanned: false,
        }
    },
    computed: {
        mutatedHeaders() {
            return this.headers.filter(
                (header) => this.removeHeaders.find((rh) => rh === header.value) === undefined
            );
        },
        visibleItems() {
            return this.hideScanned
                ? this.items.filter((item) => !this.scannedKis.includes(item.KI))
                : this.items;
        },
    },
    watch: {
        // Вкладка живёт в переиспользуемой модалке — при смене товара перечитываем список.
        value(v) {
            this.setFilter('GOODSCODE', v);
            this.options.page = 1;
            this.resetInventory();
        },
    },
    methods: {
        setFilter(attr, value) {
            this.$set(this.options.filterValues, this.options.filterAttributes.indexOf(attr), value);
        },
        // Режим инвентаризации: показываем все физически не выбывшие марки товара
        // (наклеен 3 / в обороте 5 / принят 7) без пагинации; отсканированные бледнеют
        // (или скрываются переключателем). Ничего не сохраняется.
        toggleInventory() {
            this.inventory = !this.inventory;
            this.setFilter('STATUS', this.inventory ? [3, 5, 7] : []);
            this.options.itemsPerPage = this.inventory ? -1 : 15;
            this.options.page = 1;
            if (this.inventory) {
                this.$nextTick(() => this.$refs.scanField && this.$refs.scanField.focus());
            } else {
                this.resetInventory();
            }
        },
        resetInventory() {
            this.scanInput = '';
            this.scannedKis = [];
            this.extraKis = [];
        },
        onScan() {
            const raw = this.scanInput;
            this.scanInput = '';
            if (!raw) return;
            let ki;
            try {
                ki = extractKi(raw);
            } catch (e) {
                this.$store.commit('SNACKBAR/ERROR', e.message);
                return;
            }
            if (this.items.find((item) => item.KI === ki)) {
                if (!this.scannedKis.includes(ki)) this.scannedKis.push(ki);
            } else if (!this.extraKis.includes(ki)) {
                this.extraKis.push(ki);
            }
        },
        rowClass(item) {
            return this.scannedKis.includes(item.KI) ? 'mark-scanned' : '';
        },
    },
}
</script>

<style>
.mark-scanned {
    opacity: .35;
}
</style>
