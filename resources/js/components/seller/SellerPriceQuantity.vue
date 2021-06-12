<template>
    <v-container>
        <v-row dense>
            <v-col class="d-flex justify-start">
                <span class="my-auto">Есть: <b>{{ item.quantity }}</b>&nbsp;/ Берём:</span>
                <b v-if="!hasGood || !hasInvoice">&nbsp;{{ item.orderQuantity }}</b>
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
                    <v-btn v-if="hasSellerOrder" small rounded>
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
        }
    },
    computed: {
        ...mapGetters({
            retailPrice: 'SELLER-PRICE/RETAIL_PRICE',
        }),
        hasGood() {
            return !!this.item.goodId;
        },
        hasInvoice() {
            return !!this.invoice;
        },
        hasSellerOrder() {
            return false;
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
        }
    }
}
</script>

<style scoped>

</style>
