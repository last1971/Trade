<template>
    <v-card class="m-1">
        <v-card-title class="py-2">
            <v-icon left>mdi-clipboard-list</v-icon>
            Активные заказы
            <v-spacer/>
            <v-chip
                @click="showAddDialog = true"
                style="cursor: pointer"
            >
                <v-icon left small>mdi-plus</v-icon>
                Выбрать заказ
            </v-chip>
        </v-card-title>

        <v-card-text class="d-flex flex-wrap">
            <seller-order-card
                v-for="seller in sellers"
                :key="seller.sellerId"
                :seller-id="seller.sellerId"
                :seller-name="seller.name"
            />
            <div v-if="sellers.length === 0" class="grey--text text-center pa-4 w-100">
                Нет активных заказов. Нажмите "Выбрать заказ" чтобы начать.
            </div>
        </v-card-text>

        <!-- Диалог выбора поставщика -->
        <v-dialog v-model="showAddDialog" max-width="600">
            <v-card>
                <v-card-title>Выберите поставщика</v-card-title>
                <v-card-text>
                    <v-text-field
                        v-model="search"
                        label="Поиск"
                        prepend-icon="mdi-magnify"
                        clearable
                        outlined
                        dense
                    />
                    <v-list dense max-height="400" style="overflow-y: auto">
                        <v-list-item
                            v-for="seller in filteredAvailableSellers"
                            :key="seller.sellerId"
                            @click="selectSeller(seller)"
                        >
                            <v-list-item-content>
                                <v-list-item-title>{{ seller.name }}</v-list-item-title>
                            </v-list-item-content>
                        </v-list-item>
                        <v-list-item v-if="filteredAvailableSellers.length === 0">
                            <v-list-item-content>
                                <v-list-item-title class="grey--text">Нет доступных поставщиков</v-list-item-title>
                            </v-list-item-content>
                        </v-list-item>
                    </v-list>
                </v-card-text>
                <v-card-actions>
                    <v-spacer/>
                    <v-btn text @click="showAddDialog = false">Отмена</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Диалоги для выбранного поставщика -->
        <seller-order-select-dialog
            v-if="selectedSeller"
            v-model="showOrderDialog"
            :seller-id="selectedSeller.sellerId"
            :seller-name="selectedSeller.name"
        />
    </v-card>
</template>

<script>
import {mapGetters} from "vuex";
import SellerOrderCard from "./SellerOrderCard";
import SellerOrderSelectDialog from "./SellerOrderSelectDialog";

export default {
    name: "SellerOrdersList",
    components: {SellerOrderCard, SellerOrderSelectDialog},
    data() {
        return {
            showAddDialog: false,
            showOrderDialog: false,
            selectedSeller: null,
            search: ''
        }
    },
    computed: {
        ...mapGetters({
            allSellers: 'SELLER-PRICE/SELLERS'
        }),
        activeOrderIds() {
            return this.$store.state['SELLER-ORDER'].activeOrderIds || {};
        },
        sellers() {
            // Показываем только поставщиков, у которых есть активные заказы
            return this.allSellers.filter(seller => {
                return this.activeOrderIds.hasOwnProperty(seller.sellerId);
            });
        },
        availableSellers() {
            // Только активные поставщики (isApi = true)
            return this.allSellers.filter(seller => seller.isApi);
        },
        filteredAvailableSellers() {
            if (!this.search) {
                return this.availableSellers;
            }
            const searchLower = this.search.toLowerCase();
            return this.availableSellers.filter(seller => {
                return seller.name.toLowerCase().includes(searchLower);
            });
        }
    },
    methods: {
        selectSeller(seller) {
            this.selectedSeller = seller;
            this.showAddDialog = false;
            this.showOrderDialog = true;
        }
    }
}
</script>

