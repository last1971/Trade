export default {
    data() {
        return {
            rules: {
                isInteger: n => _.isInteger(_.toNumber(n)) || 'Введите целое число',
                isNumber: n => !_.isNaN(_.toNumber(n)) || 'Введите число',
                required: v => (v === 0 || !!v) || 'Обязателный',
                positive: n => n > 0 || 'Введите положительное число',
                upperLimit: inStock => v => !this.$store.getters['GOODS-LIST/IS-RETAIL-STORE']
                    || parseInt(v) <= inStock
                    || `В магазине только ${inStock} шт.`

            },
        }
    },
    computed: {
        proxy: {
            get() {
                return this.value
            },
            set(val) {
                this.$emit('input', val)
            }
        }
    },
}
