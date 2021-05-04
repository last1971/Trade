import { mapGetters } from 'vuex';

export default {
    props: {
        item: {
            type: Object,
            required: true,
        }
    },
    computed: {
        ...mapGetters({
            toRub: 'EXCHANGE-RATE/TO_RUB',
            toUsd: 'EXCHANGE-RATE/TO_USD',
        }),
        hasPermission() {
            return this.$store.getters['AUTH/HAS_PERMISSION']('seller-price.full');
        }
    },
}
