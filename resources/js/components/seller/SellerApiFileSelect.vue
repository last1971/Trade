<template>
    <v-card class="m-1" :dark="isDark">
        <v-card-title class="text-center">
            <v-switch v-model="seller.isApi"
                      hide-details
                      dense
                      :color="color"
                      :loading="seller.loading"
                      @change="change"
            >
                <template v-slot:label>
                    <span>{{ seller.name }}</span>
                    <v-btn icon class="ml-1" outlined @click.stop="select" :disabled="!quantity">
                        {{ quantity }}
                    </v-btn>
                </template>
            </v-switch>
        </v-card-title>
    </v-card>
</template>

<script>
export default {
    name: "SellerApiFileSelect",
    props: {
        seller: {
            type: Object,
            required: true,
        }
    },
    computed: {
        quantity() {
            return this.$store.getters['SELLER-PRICE/SELLER_LINES_QUANTITY'](this.seller.sellerId);
        },
        color() {
            return this.seller.isApiError ? 'error' : 'primary';
        },
        isDark() {
            return this.$store.getters['SELLER-PRICE/SELECTED_SELLER_ID'] === this.seller.sellerId;
        }
    },
    methods: {
        select() {
            this.$store.commit('SELLER-PRICE/SELLER_SELECT', this.seller.sellerId)
        },
        change(v) {
            this.$store.commit('SELLER-PRICE/SAVE_SELLERS');
            if (v) this.$emit('seller-on', this.seller, false);
            else this.$store.commit('SELLER-PRICE/CLEAR_SELLER_DATA', this.seller.sellerId);
        }
    }
}
</script>

<style scoped>

</style>
