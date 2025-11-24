<template>
    <v-combobox
        v-model="proxy"
        :items="deliveryTimes"
        label="Срок поставки"
    ></v-combobox>
</template>

<script>
import utilsMixin from "../mixins/utilsMixin";

export default {
    name: "DeliveryTimeSelect",
    mixins: [utilsMixin],
    props: {
        value: {
            type: String,
        }
    },
    data() {
        return {
            deliveryTimes: [
                'склад',
                this.formatDays(1),
                this.formatDays(3),
                this.formatDays(5),
                this.formatDays(7),
                this.formatDays(8),
                this.formatDays(9),
                this.formatDays(10),
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
