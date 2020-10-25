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
                    />
                </slot>
            </template>
        </v-edit-dialog>
    </div>
</template>

<script>

import _ from "lodash";

export default {
    name: "GoodToList",
    props: ['value'],
    data() {
        return {
            editingValue: 1,
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
            this.editingValue = 1;
        },
        save() {
            const buyerDiscount = parseFloat(this.value.retailPrice.PRICEROZN)
                * (100 - (this.$store.getters['GOODS-LIST/BUYER'] ?
                    parseFloat(this.$store.getters['GOODS-LIST/BUYER'].SUMMA_PRICE_1) : 0)) / 100;
            let goodDiscount = parseFloat(this.value.retailPrice.PRICEROZN);
            if (this.editingValue >= this.value.retailPrice.QUANMOPT) {
                goodDiscount = parseFloat(this.value.retailPrice.PRICEMOPT);
            }
            if (this.editingValue >= this.value.retailPrice.QUANOPT) {
                goodDiscount = parseFloat(this.value.retailPrice.PRICEOPT);
            }
            const price = _.min([ buyerDiscount, goodDiscount ]);
            this.$store.commit(
                'GOODS-LIST/PUSH',
                {
                    GOODSCODE: this.value.GOODSCODE,
                    quantity: this.editingValue,
                    price,
                    discount: (1 - price / this.value.retailPrice.PRICEROZN) * 100,
                    amount: price * this.editingValue,
                }
            )
        }
    }
}
</script>

<style scoped>

</style>
