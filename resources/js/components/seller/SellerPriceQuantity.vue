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
                    <v-btn v-if="hasInvoice" small rounded @click="isAddInvoiceLine = !isAddInvoiceLine">
                        <v-icon left>mdi-text-box</v-icon>
                        В Счет
                    </v-btn>
                    <v-btn v-if="hasSellerOrder" small rounded @click="addToSellerOrder" :loading="addingToOrder">
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
    </v-container>
</template>

<script>
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
            addingToOrder: false,
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
            return this.hasGood && (this.hasInvoice || this.hasSellerOrder);
        },
        invoice() {
            const currentInvoice = this.$store.getters['INVOICE/GET-CURRENT'];
            return this.$store.getters['INVOICE/GET'](currentInvoice);
        },
        pricesForChoose() {
            const ret = [];
            const { item, toRub, retailPrice, markup } = this;
            ret.push(toRub(item.CharCode, item.price * (1 + markup / 100)));
            const price = toRub(item.CharCode, retailPrice(item));
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
            
            this.addingToOrder = true;
            try {
                // Вычисляем сумму добавляемой позиции в рублях
                const amountInRub = this.toRub(this.item.CharCode, this.item.price * this.item.orderQuantity);
                
                // Вызываем экшен для добавления строки в заказ
                await this.$store.dispatch('SELLER-ORDER/ADD_LINE', {
                    sellerId: this.item.sellerId,
                    salesId: this.activeOrder.id,
                    amountToAdd: amountInRub,
                    line: {
                        seller_id: this.item.sellerId,
                        item_id: this.item.code,
                        qty: this.item.orderQuantity,
                    }
                });
                
                this.$store.commit('SNACKBAR/SUCCESS', 'Добавлено в заказ', { root: true });
            } catch (e) {
                this.$store.commit('SNACKBAR/ERROR', e.response?.data?.message || 'Ошибка добавления в заказ', { root: true });
            } finally {
                this.addingToOrder = false;
            }
        }
    }
}
</script>

<style scoped>

</style>
