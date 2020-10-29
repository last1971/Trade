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
        <v-dialog
            v-model="hasAlready"
            persistent
            max-width="290"
        >
            <v-card>
                <v-card-title class="headline">
                    Позиция уже есть в списке
                </v-card-title>
                <v-card-actions>
                    <v-btn
                        color="green darken-1"
                        text
                        @click="afterSave(true)"
                    >
                        Заменить
                    </v-btn>
                    <v-btn
                        color="red darken-1"
                        text
                        @click="afterSave(false)"
                    >
                        Увеличить
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
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
            hasAlready: false,
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
            const { GOODSCODE } = this.value;
            if (_.find(this.$store.getters['GOODS-LIST/ALL'], { GOODSCODE })) this.hasAlready = true;
            else {
                const price = this.$store.getters['GOOD/PRICE_WITH_DISCOUNT'](GOODSCODE, this.editingValue);
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
        },
        afterSave(replace) {
            const { GOODSCODE } = this.value;
            const change = _.find(this.$store.getters['GOODS-LIST/ALL'], { GOODSCODE });
            change.quantity = replace ? this.editingValue : parseInt(change.quantity) + parseInt(this.editingValue);
            change.price = this.$store.getters['GOOD/PRICE_WITH_DISCOUNT'](GOODSCODE, change.quantity);
            change.amount = change.quantity * change.price;
            this.$store.commit('GOODS-LIST/CHANGE', change);
            this.hasAlready = false;
        }
    }
}
</script>

<style scoped>

</style>
