<template>
    <v-card flat>
        <v-card-title class="py-2">
            <v-icon left>mdi-clipboard-list</v-icon>
            Активные заказы
        </v-card-title>
        
        <v-card-text class="d-flex flex-wrap">
            <seller-order-card
                :seller-id="857"
                seller-name="Компел"
            />
            
            <seller-order-card
                :seller-id="999"
                seller-name="Промэлектроника"
            />
        </v-card-text>
    </v-card>
</template>

<script>
import SellerOrderCard from "./SellerOrderCard";

export default {
    name: "SellerOrdersList",
    components: {SellerOrderCard}
}
</script>

