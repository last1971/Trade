<template>
    <v-expansion-panels>
        <v-expansion-panel>
            <v-expansion-panel-header>
                <v-row dense align="center">
                    <v-col v-if="!currentInvoice || !invoice"
                        >Счет не выбран</v-col
                    >
                    <v-col v-else>
                        Счет № {{ invoice.NS }} для
                        {{ invoice.buyer.SHORTNAME }}
                        <v-btn
                            icon
                            small
                            title="Открыть счет"
                            :to="{
                                name: 'invoice',
                                params: { id: currentInvoice },
                            }"
                        >
                            <v-icon>mdi-open-in-new</v-icon>
                        </v-btn>
                        <v-btn
                            icon
                            small
                            :loading="pdfDownloading"
                            title="Скачать PDF"
                            @click.stop="openPdfMenu"
                        >
                            <v-icon color="red">mdi-adobe-acrobat</v-icon>
                        </v-btn>

                        <v-btn
                            icon
                            small
                            color="error"
                            title="Убрать счёт"
                            @click.stop="
                                $store.commit('INVOICE/SET-CURRENT', null)
                            "
                        >
                            <v-icon>mdi-close</v-icon>
                        </v-btn>
                        <invoice-pdf-menu
                            v-if="invoice"
                            :value="invoice"
                            :pdf-dialog="pdfDialog"
                            @close="pdfDialog = false"
                            @downloading="setPdfDownloading"
                        />
                    </v-col>
                    <v-col v-if="compelOrder">
                        Компэл № {{ compelOrder.number }} от
                        {{ compelOrder.date | formatDate }}
                    </v-col>
                    <v-col v-if="promelecOrder">
                        Пром № {{ promelecOrder.number }} от
                        {{ promelecOrder.date | formatDate }}
                    </v-col>
                    <v-col v-if="allSellers"
                        >Все {{ sellers.length }} поставщиков</v-col
                    >
                    <v-col v-else
                        >Активных поставщиков {{ activeSellers }} из
                        {{ sellers.length }}</v-col
                    >
                </v-row>
            </v-expansion-panel-header>
            <v-expansion-panel-content>
                <div class="d-flex flex-column">
                    <div class="d-flex">
                        <!-- Счет -->
                        <invoice-card class="flex-grow-1 flex-shrink-0" />

                        <!-- Активные заказы -->
                        <seller-orders-list class="flex-grow-1 flex-shrink-0" />

                        <!-- Поставщики -->
                        <v-card
                            class="m-1 flex-grow-0 flex-shrink-1 d-flex align-center"
                        >
                            <v-chip-group column class="mx-3">
                                <seller-api-chip
                                    v-for="seller in sellers"
                                    :key="seller.sellerId"
                                    :seller="seller"
                                    @save-sellers="saveSellers"
                                />
                                <seller-api-disabled
                                    v-if="!allSellers"
                                    @save-sellers="saveSellers"
                                    @seller-on="sellerOn"
                                />
                            </v-chip-group>
                        </v-card>
                    </div>
                </div>
            </v-expansion-panel-content>
        </v-expansion-panel>
    </v-expansion-panels>
</template>

<script>
import { mapGetters } from "vuex";
import SellerApiChip from "./SellerApiChip";
import InvoiceCard from "../../invoice/InvoiceCard";
import SellerApiDisabled from "./SellerApiDisabled";
import SellerOrdersList from "../SellerOrdersList";
import InvoicePdfMenu from "../../invoice/InvoicePdfMenu";
import invoice from "../../../store/invoice";

export default {
    name: "SellerApiFileSelectNew",
    components: {
        SellerApiDisabled,
        SellerApiChip,
        InvoiceCard,
        InvoicePdfMenu,
        SellerOrdersList,
    },
    data() {
        return {
            pdfDialog: false,
            pdfDownloading: false,
        };
    },
    created() {
        this.tryLoadInvoice(this.currentInvoice);

        // Загружаем заказы для всех поставщиков у которых есть активные ID
        const activeOrderIds = this.$store.state['SELLER-ORDER'].activeOrderIds || {};
        const sellerIdsToLoad = Object.keys(activeOrderIds).map(id => parseInt(id));

        if (sellerIdsToLoad.length > 0) {
            Promise.all(
                sellerIdsToLoad.map((id) =>
                    this.$store.dispatch("SELLER-ORDER/SYNC_SELLER", id)
                )
            ).catch(() => {
                /* обработка ошибок, если нужна */
            });
        }
    },
    computed: {
        ...mapGetters({
            sellers: "SELLER-PRICE/SELLERS",
            getActiveOrder: "SELLER-ORDER/GET_ACTIVE_ORDER",
            currentInvoice: "INVOICE/GET-CURRENT",
        }),
        compelOrder() {
            const order = this.getActiveOrder(857); // seller_id Compel
            return order || null;
        },
        promelecOrder() {
            const order = this.getActiveOrder(860); // seller_id Compel
            return order || null;
        },
        invoice() {
            return (
                this.$store.getters["INVOICE/GET"](this.currentInvoice) || null
            );
        },
        activeSellers() {
            return this.sellers.filter((v) => v.isApi).length;
        },
        allSellers() {
            return this.activeSellers === this.sellers.length;
        },
    },
    watch: {
        activeSellers() {
            if (this.activeSellers === 0) {
                this.sellers.forEach((seller) => {
                    seller.isApi = true;
                    this.sellerOn(seller);
                });
            }
            this.saveSellers();
        },
    },
    methods: {
        saveSellers() {
            this.$store.commit("SELLER-PRICE/SAVE_SELLERS");
        },
        sellerOn(seller) {
            this.$emit("seller-on", seller, false);
        },
        async tryLoadInvoice(invoiceId) {
            if (invoiceId && !this.$store.getters["INVOICE/GET"](invoiceId)) {
                await this.$store.dispatch("INVOICE/GET", {
                    id: invoiceId,
                    query: {
                        with: ["buyer", "employee", "firm"],
                        aggregateAttributes: [
                            "invoiceLinesCount",
                            "invoiceLinesSum",
                            "cashFlowsSum",
                            "transferOutLinesSum",
                        ],
                    },
                });
            }
        },
        openPdfMenu() {
            if (this.invoice) {
                this.pdfDialog = true;
            }
        },
        setPdfDownloading(val) {
            this.pdfDownloading = val;
        },
    },
};
</script>

<style scoped></style>
