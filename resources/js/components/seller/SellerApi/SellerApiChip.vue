<template>
    <v-chip
        :key="seller.sellerId"
        v-if="seller.isApi"
        :filter="quantity > 0"
        outlined
        close
        v-model="isFiltered"
        @click:close="close"
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
            set() {
                if (this.quantity > 0) {
                    this.$store.commit('SELLER-PRICE/SELLER_SELECT', this.seller.sellerId)
                }
            }
        }
    },
    methods:{
        close() {
            this.seller.isApi = !this.seller.isApi;
            this.$store.commit('SELLER-PRICE/CLEAR_SELLER_DATA', this.seller.sellerId);
            this.$emit('save-sellers');
        },
    }
}
</script>

<style scoped>

</style>
