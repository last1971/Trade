<template>
    <v-expansion-panels>
        <v-expansion-panel>
            <v-expansion-panel-header>
                <v-row dense>
                    <v-col v-if="!currentInvoice || !invoice"
                        >Счет не выбран</v-col
                    >
                    <v-col v-else
                        >Счет № {{ invoice.NS }} для
                        {{ invoice.buyer.SHORTNAME }}</v-col
                    >
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
import invoice from "../../../store/invoice";

export default {
    name: "SellerApiFileSelectNew",
    components: {
        SellerApiDisabled,
        SellerApiChip,
        InvoiceCard,
        SellerOrdersList,
    },
    created() {
        this.tryLoadInvoice(this.currentInvoice);
        const sellerIds = [857, 860]; // Compel и Promelec
        Promise.all(
           sellerIds.map(id => this.$store.dispatch('SELLER-ORDER/SYNC_SELLER', id))
       ).catch(() => {/* обработка ошибок, если нужна */});
    },
    computed: {
        ...mapGetters({
            sellers: "SELLER-PRICE/SELLERS",
            currentInvoice: "INVOICE/GET-CURRENT",
        }),
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
    },
};
</script>

<style scoped></style>
