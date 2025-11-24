export default {
    data() {
        return {
            rules: {
                isInteger: n => _.isInteger(_.toNumber(n)) || 'Введите целое число',
                isNumber: n => !_.isNaN(_.toNumber(n)) || 'Введите число',
                required: v => (v === 0 || !!v) || 'Обязателный',
                positive: n => n > 0 || 'Введите положительное число',
                max: m => v => (m >= v) || 'Введите число меньше ' + m,
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
    methods: {
        $_utilsMixin_isValid(value, rules) {
            return rules.reduce((result, rule) => rule(value) === true && result, true);
        },
        /**
         * Склонение числительных с существительными
         * @param {number} number - число
         * @param {string} one - форма для 1 (день)
         * @param {string} few - форма для 2-4 (дня)
         * @param {string} many - форма для 5+ (дней)
         * @returns {string} - правильная форма
         */
        pluralize(number, one, few, many) {
            const n = Math.abs(number) % 100;
            const n1 = n % 10;

            if (n > 10 && n < 20) return many;
            if (n1 > 1 && n1 < 5) return few;
            if (n1 === 1) return one;
            return many;
        },
        /**
         * Форматирование количества дней с правильным склонением
         * @param {number} days - количество дней
         * @returns {string} - "1 день", "2 дня", "5 дней"
         */
        formatDays(days) {
            if (days === 0) return 'склад';
            return `${days} ${this.pluralize(days, 'день', 'дня', 'дней')}`;
        }
    }
}
