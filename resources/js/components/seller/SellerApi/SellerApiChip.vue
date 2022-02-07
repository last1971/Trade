<template>
    <v-chip
        :key="seller.sellerId"
        v-if="seller.isApi"
        :filter="quantity > 0"
        outlined
        close
        v-model="isFiltered"
        @click:close="close(seller)"
    >
        <v-btn class="mr-1" icon outlined small :loading="seller.loading" disabled>
            {{ quantity }}
        </v-btn>
        {{ seller.name }}
    </v-chip>
</template>

<script>
export default {
    name: "SellerApiChip",
    props: {
        seller: {
            type: Object,
            required: true,
        }
    },
    data() {
        return {
            test: false,
        }
    },
    computed: {
        quantity() {
            return this.$store.getters['SELLER-PRICE/SELLER_LINES_QUANTITY'](this.seller.sellerId);
        },
        color() {
            return this.seller.isApiError ? 'error' : undefined;
        },
        isFiltered: {
            get() {
                return this.$store.getters['SELLER-PRICE/SELECTED_SELLER_ID'] === this.seller.sellerId;
            },
            set(v) {
                console.log(v);
                this.$store.commit('SELLER-PRICE/SELLER_SELECT', this.seller.sellerId)
            }
        }
    },
    methods:{
        close(seller) {
            seller.isApi = !seller.isApi;
        },
    }
}
</script>

<style scoped>

</style>
