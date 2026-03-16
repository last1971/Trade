<template>
    <v-tooltip bottom :disabled="!isBlocked" v-if="seller.isApi">
        <template v-slot:activator="{ on, attrs }">
            <v-chip
                :key="seller.sellerId"
                :filter="quantity > 0"
                outlined
                close
                v-model="isFiltered"
                @click:close="close"
                :color="color"
                v-bind="attrs"
                v-on="on"
            >
                <v-btn class="mr-1" icon outlined small :loading="seller.loading" disabled>
                    {{ quantity }}
                </v-btn>
                {{ seller.name }}
            </v-chip>
        </template>
        <div>
            <div>API недоступен</div>
            <div>Данные из БД до {{ blockedUntilFormatted }}</div>
            <div v-if="seller.lastError" style="max-width: 300px; word-break: break-word;">{{ seller.lastError }}</div>
        </div>
    </v-tooltip>
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
        isBlocked() {
            return this.seller.blockedUntil && new Date(this.seller.blockedUntil) > new Date();
        },
        color() {
            if (this.isBlocked) return 'warning';
            return this.seller.isApiError ? 'error' : undefined;
        },
        blockedUntilFormatted() {
            if (!this.seller.blockedUntil) return '';
            return new Date(this.seller.blockedUntil).toLocaleTimeString('ru-RU', {
                hour: '2-digit',
                minute: '2-digit',
            });
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
            if (this.$store.getters['SELLER-PRICE/SELECTED_SELLER_ID'] === this.seller.sellerId)
                this.$store.commit('SELLER-PRICE/SELLER_SELECT', this.seller.sellerId);
            this.$store.commit('SELLER-PRICE/CLEAR_SELLER_DATA', this.seller.sellerId);
            this.$emit('save-sellers');
        },
    }
}
</script>

<style scoped>

</style>
