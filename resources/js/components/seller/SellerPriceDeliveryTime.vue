<template>
    <v-container>
        <v-row dense>
            <v-col>
                <b>{{ item.deliveryTime }} дней</b>
            </v-col>
        </v-row>
        <v-row dense>
            <v-col class="text-left">
                <v-btn :disabled="!item.isCache" plain small tile @click="$emit('update')" left>
                    {{ item.updatedAt | formatDateTime}}
                </v-btn>
            </v-col>
        </v-row>
        <v-row v-if="hasPermission" dense>
            <v-col>
                {{ sellerRemark(item) }}
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
import sellerPriceMixin from "../../mixins/sellerPriceMixin";

export default {
    name: "SellerPriceDeliveryTime",
    mixins: [sellerPriceMixin],
    methods: {
        sellerRemark(item) {
            if (!item.options) return 'С К Л А Д';
            if (item.options.location_id) return item.options.location_id;
            return item.options.vend_type;
        },
    },
}
</script>

<style scoped>

</style>
