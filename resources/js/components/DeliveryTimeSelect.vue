<template>
    <v-combobox
        v-model="proxy"
        :items="deliveryTimes"
        label="Срок поставки"
    ></v-combobox>
</template>

<script>
export default {
    name: "DeliveryTimeSelect",
    props: {
        value: {
            type: String,
        }
    },
    data() {
        return {
            deliveryTimes: [
                'склад',
                '1 день',
                '3 дня',
                '5 дней',
                '7 дней',
                '8 дней',
                '9 дней',
                '10 дней',
                '2 недели',
                '3 недели',
                '4 недели',
                '5 недель',
                '6 недель',
                '7 недель',
                '8 недель',
                '9 недель',
                '10 недель',
            ]
        }
    },
    computed: {
        proxy: {
            get() {
                return this.value;
            },
            set(v) {
                this.$emit('input', v);
            }
        }
    }
 }
</script>

<style scoped>

</style>
