<template>
    <div>
        <v-edit-dialog @open="open" @save="save">
            <slot name="cell">
                {{ warehouse }} /
                {{ retailStore }} /
                <b class="primary--text">{{ free }}</b>
            </slot>
            <template v-if="!simple" v-slot:input>
                <slot :editingv.sync="editingValue" name="input">
                    <v-text-field
                        label="Добавить в список"
                        v-model="editingValue"
                        :rules="[rules.isInteger, rules.required, rules.positive, rules.upperLimit(retailStore)]"
                    />
                </slot>
            </template>
        </v-edit-dialog>
    </div>
</template>

<script>

import _ from "lodash";
import utilsMixin from "../../mixins/utilsMixin";

export default {
    name: "GoodToList",
    mixins: [utilsMixin],
    props: ['value'],
    data() {
        return {
            editingValue: 0,
        }
    },
    computed: {
        retailStore() {
            return this.value.retailStore ? this.value.retailStore.QUAN : 0;
        },
        warehouse() {
            return this.value.warehouse ? this.value.warehouse.QUAN : 0;
        },
        reserved() {
            return this.value.reservesQuantity;
        },
        free() {
            return this.warehouse + this.retailStore - this.reserved;
        },
        simple() {
            if (!this.$store.getters['GOODS-LIST/OPENED']) return true;
            return !!(this.$store.getters['GOODS-LIST/IS-RETAIL-STORE'] && this.free < 1);
        }
    },
    methods: {
        open() {
            const v = this.$store.getters['GOODS-LIST/GET'](this.value.GOODSCODE);
            this.editingValue = v ? v.quantity : 0;
        },
        save() {
            const isPossible = [
                this.rules.isInteger,
                this.rules.required,
                this.rules.positive,
                this.rules.upperLimit(this.retailStore)
            ].reduce((a, v) => a && v(this.editingValue) === true, true);
            if (!isPossible) return;
            const { GOODSCODE } = this.value;
            if (this.$store.getters['GOODS-LIST/GET'](GOODSCODE)) {
                this.$store.commit('GOODS-LIST/CHANGE-QUANTITY', {
                    GOODSCODE,
                    quantity: parseInt(this.editingValue)
                });
            }
            else {
                const price = this.$store.getters['GOOD/PRICE_WITH_DISCOUNT'](GOODSCODE, this.editingValue);
                this.$store.commit(
                    'GOODS-LIST/PUSH',
                    {
                        GOODSCODE: this.value.GOODSCODE,
                        quantity: parseInt(this.editingValue),
                        price,
                        discount: (1 - price / this.value.retailPrice.PRICEROZN) * 100,
                        amount: price * this.editingValue,
                        retailOrderLineId: null,
                    }
                )
            }
        },
    }
}
</script>

<style scoped>

</style>
