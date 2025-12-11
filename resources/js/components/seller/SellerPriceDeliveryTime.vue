<template>
    <v-container>
        <v-row dense>
            <v-col>
                <b>{{ formatDays(item.deliveryTime) }}</b>
                <v-btn icon x-small @click="copyToClipboard" title="Копировать в буфер">
                    <v-icon small>mdi-content-copy</v-icon>
                </v-btn>
            </v-col>
        </v-row>
        <v-row dense>
            <v-col class="text-left">
                <v-btn :disabled="!item.isCache" plain small tile @click="$emit('update')" left>
                    {{ item.updatedAt | formatDateTime}}
                </v-btn>
            </v-col>
        </v-row>
        <v-row v-if="hasPermission" dense>
            <v-col>
                {{ sellerRemark(item) }}
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
import sellerPriceMixin from "../../mixins/sellerPriceMixin";
import utilsMixin from "../../mixins/utilsMixin";

export default {
    name: "SellerPriceDeliveryTime",
    mixins: [sellerPriceMixin, utilsMixin],
    methods: {
        sellerRemark(item) {
            if (!item.options) return 'С К Л А Д';
            if (item.options.location_id) return item.options.location_id;
            return item.options.vend_type;
        },
        showName(item) {
            let ret = item.name;
            if (item.case && item.case.trim().length > 0) ret += ' / ' + item.case;
            if (item.producer && item.producer.trim().length > 0) ret += ' / ' + item.producer;
            return ret;
        },
        async copyToClipboard() {
            const item = this.item;
            const name = this.showName(item);
            const qty = item.orderQuantity;
            const markupMultiplier = 1 + this.markup / 100;
            const priceRub = this.toRub(item.CharCode, item.price * markupMultiplier, item.sellerId);
            const priceUsd = this.toUsd(item.CharCode, item.price * markupMultiplier);
            const totalRub = this.toRub(item.CharCode, item.price * qty * markupMultiplier, item.sellerId);
            const totalUsd = this.toUsd(item.CharCode, item.price * qty * markupMultiplier);
            const delivery = this.formatDays(item.deliveryTime);

            const formatNum = (n) => n.toFixed(2).replace('.', ',');

            const text = `${name} ${qty} шт по ${formatNum(priceRub)} ₽ ($${formatNum(priceUsd)}) всего ${formatNum(totalRub)} ₽ ($${formatNum(totalUsd)}) срок ${delivery}`;

            try {
                await navigator.clipboard.writeText(text);
            } catch (err) {
                console.error('Failed to copy:', err);
            }
        },
    },
}
</script>

<style scoped>

</style>
