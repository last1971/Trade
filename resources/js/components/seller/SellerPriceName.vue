<template>
    <v-container>
        <v-row dense align="center">
            <v-col>
                <b>{{ showName(item) }}</b>
                <chz-mark :flag="marking" :code="item.goodId"/>
            </v-col>
            <v-col cols="auto">
                <mpn-card-button :item="item"/>
            </v-col>
        </v-row>
        <v-row dense>
            <v-col class="text-caption">
                {{ showRemark(item) }}
            </v-col>
        </v-row>
        <v-row v-if="tnved" dense>
            <v-col class="text-caption">
                ТН ВЭД {{ tnved }}
            </v-col>
        </v-row>
        <v-row v-if="hasPermission" dense>
            <v-col>
                <good-in-string-select v-model="item.goodId"
                                       :disabled="disabled"
                                       @input="save"
                                       :search="item.name"
                                       :new-good="item"
                />
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
import GoodInString from "../good/GoodInString";
import sellerPriceMixin from "../../mixins/sellerPriceMixin";
import GoodSelect from "../good/GoodSelect";
import GoodInStringSelect from "../good/GoodInStringSelect";
import MpnCardButton from "../mpn/MpnCardButton";
import ChzMark from "../good/ChzMark";
export default {
    name: "SellerPriceName",
    components: {ChzMark, MpnCardButton, GoodInStringSelect, GoodSelect, GoodInString},
    mixins: [sellerPriceMixin],
    computed: {
        disabled() {
            return !this.$store.getters['AUTH/HAS_PERMISSION']('seller-good.update');
        },
        // Признак маркировки и ТН ВЭД поставщик кладёт в options склада (pricing-nest).
        // Не прислал признак — null, значок решает по нашему товару (goodId).
        marking() {
            const v = this.item.options ? this.item.options.marking : undefined;
            return typeof v === 'boolean' ? v : null;
        },
        tnved() {
            return this.item.options && this.item.options.tnved
                ? String(this.item.options.tnved).trim()
                : '';
        },
    },
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
        async save() {
            await this.$store.dispatch('SELLER-PRICE/SAVE_SELLER_PRICE', this.item);
        }
    }
}
</script>

<style scoped>

</style>
