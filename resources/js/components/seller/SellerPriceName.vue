<template>
    <v-container>
        <v-row dense>
            <v-col>
                <b>{{ showName(item) }}</b>
            </v-col>
        </v-row>
        <v-row dense>
            <v-col class="text-caption">
                {{ showRemark(item) }}
            </v-col>
        </v-row>
        <v-row v-if="hasPermission" dense>
            <v-col>
                <good-in-string v-if="good(item.goodId)" :value="good(item.goodId)" />
                <div v-else>А Н Е Т У</div>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
import GoodInString from "../good/GoodInString";
import sellerPriceMixin from "../../mixins/sellerPriceMixin";
export default {
    name: "SellerPriceName",
    components: {GoodInString},
    mixins: [sellerPriceMixin],
    methods: {
        showName(item) {
            let ret = item.name;
            if (item.case && item.case.trim().length > 0) ret += ' / ' + item.case;
            if (item.producer && item.producer.trim().length > 0) ret += ' / ' + item.producer;
            return ret;
        },
        good(id) {
            return  this.$store.getters['GOOD/GET'](id);
        },
        showRemark(item) {
            let ret = item.remark ? item.remark.trim() : item.remark;
            return ret || 'Без комментариев';
        },
    }
}
</script>

<style scoped>

</style>
