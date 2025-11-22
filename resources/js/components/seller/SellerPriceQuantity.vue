<template>
    <v-container>
        <v-row dense>
            <v-col class="d-flex justify-start">
                <span class="my-auto">Есть: <b>{{ item.quantity }}</b>&nbsp;/ Берём:</span>
                <b v-if="!canAddToInvoiceOrOrder">&nbsp;{{ item.orderQuantity }}</b>
                <v-speed-dial v-else :open-on-hover="true" direction="bottom">
                    <template v-slot:activator>
                        <v-btn plain small rounded>
                            {{ item.orderQuantity }}
                        </v-btn>
                    </template>
                    <v-btn v-if="hasInvoice && hasGood" small rounded @click="isAddInvoiceLine = !isAddInvoiceLine">
                        <v-icon left>mdi-text-box</v-icon>
                        В Счет
                    </v-btn>
                    <v-btn v-if="hasSellerOrder && item.code" small rounded @click="addToSellerOrder">
                        <v-icon left>mdi-clipboard-arrow-left</v-icon>
                        В Заказ
                    </v-btn>
                </v-speed-dial>
            </v-col>
        </v-row>
        <v-row dense>
            <v-col class="text-caption">
                Упак: {{ item.packageQuantity }} / Кратно: {{ item.multiplicity }}
            </v-col>
        </v-row>
        <v-row v-if="hasPermission" dense>
            <v-col>
                Min: {{ item.minQuantity }} / Max: {{ item.maxQuantity }}
            </v-col>
        </v-row>
        <v-dialog v-if="hasInvoice" v-model="isAddInvoiceLine" :key="itemId">
            <invoice-line-add :invoice="invoice"
                              :new-invoice-line="item"
                              :prices-for-choose="pricesForChoose"
                              @close="isAddInvoiceLine = false"
                              @closeWithReload="invoiceUpdate"
            />
        </v-dialog>
        <v-dialog v-model="showConfirmDialog" max-width="500">
            <v-card>
                <v-card-title>Подтвердите действие на localhost</v-card-title>
                <v-card-text>
                    <p>Товар уже есть в заказе.</p>
                    <p>Текущее количество: <b>{{ existingLineForUpdate ? existingLineForUpdate.sales_qty : '' }}</b></p>
                    <p>Новое количество (или отмена для отмены): <b>{{ item.orderQuantity }}</b></p>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn text @click="cancelUpdate">Отмена</v-btn>
                    <v-btn color="primary" @click="confirmUpdate">OK</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-container>
</template>

<script>
import { hasIn } from "lodash";
import sellerPriceMixin from "../../mixins/sellerPriceMixin";
import InvoiceLineAdd from "../invoice/InvoiceLineAdd";
import {mapGetters} from "vuex";

export default {
    name: "SellerPriceQuantity",
    components: {InvoiceLineAdd},
    mixins: [sellerPriceMixin],
    data() {
        return {
            isAddInvoiceLine: false,
            additionalKey: 0,
            showConfirmDialog: false,
            existingLineForUpdate: null,
        }
    },
    computed: {
        ...mapGetters({
            retailPrice: 'SELLER-PRICE/RETAIL_PRICE',
            getActiveOrder: 'SELLER-ORDER/GET_ACTIVE_ORDER',
        }),
        hasGood() {
            return !!this.item.goodId;
        },
        hasInvoice() {
            return !!this.invoice;
        },
        hasSellerOrder() {
            return !!this.activeOrder;
        },
        activeOrder() {
            return this.getActiveOrder(this.item.sellerId);
        },
        canAddToInvoiceOrOrder() {
            // Для счета нужен goodId, для заказа достаточно code
            const canAddToInvoice = this.hasGood && this.hasInvoice;
            const canAddToOrder = this.item.code && this.hasSellerOrder;
            return canAddToInvoice || canAddToOrder;
        },
        invoice() {
            const currentInvoice = this.$store.getters['INVOICE/GET-CURRENT'];
            return this.$store.getters['INVOICE/GET'](currentInvoice);
        },
        pricesForChoose() {
            const ret = [];
            const { item, toRub, retailPrice, markup } = this;
            ret.push(toRub(item.CharCode, item.price * (1 + markup / 100), item.sellerId));
            const price = toRub(item.CharCode, retailPrice(item), item.sellerId);
            if (price > 0) ret.push(price);
            return ret;
        },
        itemId() {
            return this.additionalKey + '-' + this.item.id;
        }
    },
    methods: {
        async invoiceUpdate() {
            try {
                await this.$store.dispatch(
                    'INVOICE/GET',
                    {
                        id: this.$store.getters['INVOICE/GET-CURRENT'],
                        query: {
                            with: ['buyer', 'employee', 'firm'],
                            aggregateAttributes: [
                                'invoiceLinesCount', 'invoiceLinesSum', 'cashFlowsSum', 'transferOutLinesSum'
                            ],
                        }
                    }
                );
                this.additionalKey += 1;
            } catch (e) {

            }
            this.isAddInvoiceLine = false;
        },
        async addToSellerOrder() {
            if (!this.activeOrder || !this.item.code) {
                this.$store.commit('SNACKBAR/ERROR', 'Не удалось добавить в заказ', { root: true });
                return;
            }
            
            // Получаем строки заказа из кеша
            const orderLines = this.$store.getters['SELLER-ORDER/GET_ORDER_LINES'](this.activeOrder.id);

            // Проверяем, есть ли уже строка с таким item_id или good_id
            const existingLine = orderLines.lines.find(line => {
                // Для API поставщиков (Compel, Promelec) сравниваем по item_id
                if (line.item_id) {
                    return String(line.item_id) === String(this.item.code);
                }
                // Для Firebird поставщиков сравниваем по good_id
                if (line.good_id && this.item.goodId) {
                    return String(line.good_id) === String(this.item.goodId);
                }
                return false;
            });

            if (existingLine) {
                // Строка существует - показываем диалог подтверждения
                this.existingLineForUpdate = existingLine;
                this.showConfirmDialog = true;
            } else {
                // Строки нет - добавляем новую
                await this.addNewLine();
            }
        },
        cancelUpdate() {
            this.showConfirmDialog = false;
            this.existingLineForUpdate = null;
        },
        async confirmUpdate() {
            this.showConfirmDialog = false;

            try {
                const newQuantity = this.item.orderQuantity;
                const priceInRub = this.toRub(this.item.CharCode, this.item.price, this.item.sellerId);
                const oldAmount = this.existingLineForUpdate.price * this.existingLineForUpdate.sales_qty;
                const newAmount = priceInRub * newQuantity;
                const amountDiff = newAmount - oldAmount;
                
                await this.$store.dispatch('SELLER-ORDER/UPDATE_LINE_QUANTITY', {
                    salesId: this.activeOrder.id,
                    sellerId: this.item.sellerId,
                    lineId: this.existingLineForUpdate.line_id,
                    quantity: newQuantity,
                    amountDiff: amountDiff
                });
                
                this.$store.commit('SNACKBAR/PUSH', { text: 'Количество обновлено', color: 'success', status: true }, { root: true });
            } catch (e) {
                this.$store.commit('SNACKBAR/ERROR', e.response?.data?.message || 'Ошибка обновления количества', { root: true });
            } finally {
                this.existingLineForUpdate = null;
            }
        },
        async addNewLine() {
            try {
                const priceInRub = this.toRub(this.item.CharCode, this.item.price, this.item.sellerId);
                const amountInRub = priceInRub * this.item.orderQuantity;
                
                const lineData = {
                    line_id: null,
                    item_id: this.item.code,
                    item_name: this.item.name || '',
                    brend: this.item.producer || this.item.brend || '',
                    package_name: this.item.packageName || '',
                    sales_qty: this.item.orderQuantity,
                    price: priceInRub,
                    amount: amountInRub,
                    currency_code: 'RUB',
                    reserve_qty: this.item.orderQuantity,
                    reservation_end: null
                };

                await this.$store.dispatch('SELLER-ORDER/ADD_LINE', {
                    sellerId: this.item.sellerId,
                    salesId: this.activeOrder.id,
                    amountToAdd: amountInRub,
                    line: {
                        seller_id: this.item.sellerId,
                        item_id: this.item.code,
                        item_name: this.item.name,
                        qty: this.item.orderQuantity,
                        price: priceInRub,
                        good_id: this.item.goodId || null,
                        price_data: this.item,
                    },
                    lineData: lineData
                });
                
                this.$store.commit('SNACKBAR/PUSH', { text: 'Добавлено в заказ', color: 'success', status: true }, { root: true });
            } catch (e) {
                this.$store.commit('SNACKBAR/ERROR', e.response?.data?.message || 'Ошибка добавления в заказ', { root: true });
            }
        }
    }
}
</script>

<style scoped>

</style>
