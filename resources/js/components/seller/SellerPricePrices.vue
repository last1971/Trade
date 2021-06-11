<template>
    <v-container>
        <v-row dense>
            <v-col>
                <b>
                    {{ toRub(item.CharCode, item.price) | formatRub }}
                    ({{ toUsd(item.CharCode, item.price) | formatUsd }})
                </b>
            </v-col>
        </v-row>
        <v-row v-if="hasPermission" dense>
            <v-col class="text-caption">
                {{ toRub(item.CharCode, retailPrice(item)) | formatRub }}
                ({{ toUsd(item.CharCode, retailPrice(item)) | formatUsd }})
            </v-col>
        </v-row>
        <v-row v-else dense>
            <v-col class="text-caption">
                <b>
                    {{ toRub(item.CharCode, item.price * item.orderQuantity) | formatRub }}
                    ({{ toUsd(item.CharCode, item.price * item.orderQuantity) | formatUsd }})
                </b>
            </v-col>
        </v-row>
        <v-row v-if="hasPermission" dense>
            <v-col>
                {{ toRub(item.CharCode, item.price * (1 + markup / 100)) | formatRub }}
                ({{ toUsd(item.CharCode, item.price * (1 + markup / 100)) | formatUsd }})
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
import sellerPriceMixin from "../../mixins/sellerPriceMixin";
import { mapGetters } from 'vuex';

export default {
    name: "SellerPricePrices",
    mixins: [sellerPriceMixin],
    computed: {
        ...mapGetters({
            retailPrice: 'SELLER-PRICE/RETAIL_PRICE',
        }),
    }
}
</script>

<style scoped>

</style>
