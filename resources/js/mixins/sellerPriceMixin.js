import { mapGetters } from 'vuex';

export default {
    props: {
        item: {
            type: Object,
            required: true,
        },
        markup: {
            type: [Number, String],
            validate: (v) => !_.isNaN(_.toNumber(v)),
            default: 0,
        },
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
