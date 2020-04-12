export default {
    data() {
        return {
            rules: {
                isInteger: n => _.isInteger(_.toNumber(n)) || 'Введите целое число',
                isNumber: n => !_.isNaN(_.toNumber(n)) || 'Введите число',
                required: v => (v === 0 || !!v) || 'Обязателный'
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