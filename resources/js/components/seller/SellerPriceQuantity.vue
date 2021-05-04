<template>
    <v-container>
        <v-row dense>
            <v-col>
                Есть: <b>{{ item.quantity }}</b> / Берём:
                <v-btn plain small tile>
                    {{ item.orderQuantity }}
                </v-btn>
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
    </v-container>
</template>

<script>
import sellerPriceMixin from "../../mixins/sellerPriceMixin";

export default {
    name: "SellerPriceQuantity",
    mixins: [sellerPriceMixin],
}
</script>

<style scoped>

</style>
