<template>
    <v-card>
        <v-card-title class="headline">
            <span class="headline">Строка заказа</span>
            <v-spacer/>
            <v-btn @click="$emit('close')" icon right>
                <v-icon color="red">
                    mdi-close
                </v-icon>
            </v-btn>
        </v-card-title>
        <v-container>
            <v-row>
                <v-col cols="12">
                    <good-select v-model="orderLine.GOODSCODE"/>
                </v-col>
            </v-row>
            <v-row>
                <v-col>
                    <v-text-field
                        :rules="[rules.isInteger, rules.required, rules.positive]"
                        v-model="orderLine.QUAN"
                        label="Количество"
                        @input="changeQUAN"
                    />
                </v-col>
                <v-col>
                    <v-text-field
                        :rules="[rules.isNumber, rules.required, rules.positive]"
                        label="Цена без НДС"
                        v-model="orderLine.priceWithoutVat"
                        @input="changePriceWithoutVat"
                    />
                </v-col>
                <v-col>
                    <v-text-field
                        :rules="[rules.isNumber, rules.required, rules.positive]"
                        label="Цена с НДС"
                        v-model="orderLine.PRICE"
                        @input="changePRICE"
                    />
                </v-col>
                <v-col>
                    <v-text-field
                        :rules="[rules.isNumber, rules.required, rules.positive]"
                        label="Сумма без НДС"
                        v-model="orderLine.sumWithoutVat"
                        @input="changeSumWithoutVat"
                    />
                </v-col>
                <v-col>
                    <v-text-field
                        :rules="[rules.isNumber, rules.required, rules.positive]"
                        label="Сумма с НДС"
                        v-model="orderLine.SUMMAP"
                        @input="changeSUMMAP"
                    />
                </v-col>
            </v-row>
            <v-row>
                <v-col>
                    <v-text-field
                        label="Страна"
                        v-model="orderLine.STRANA"
                    />
                </v-col>
                <v-col>
                    <v-text-field
                        label="ГТД"
                        v-model="orderLine.GTD"
                    />
                </v-col>
            </v-row>
            <v-row>
                <v-col col="10">
                    <v-text-field
                        label="Примечание"
                        v-model="orderLine.PRIM"
                    />
                </v-col>
                <v-col col="2">
                    <v-btn :disabled="!savePossible" @click="save" fab :loading="loading">
                        <v-icon color="green">mdi-content-save</v-icon>
                    </v-btn>
                </v-col>
            </v-row>
        </v-container>
    </v-card>
</template>

<script>
import GoodSelect from '../good/GoodSelect'
import utilsMixin from "../../mixins/utilsMixin";
export default {
    name: "OrderLineAdd",
    components: { GoodSelect },
    mixins:[utilsMixin],
    props: {
        order: { type: Object, required: true },
    },
    data() {
        return {
            orderLine: {
                MASTER_ID: this.order.ID,
                QUAN: null,
                priceWithoutVat: null,
                PRICE: null,
                sumWithoutVat: null,
                SUMMAP: null,
            },
            loading: false,
        }
    },
    computed: {
        isRightQUAN() {
            const { rules } = this;
            return this.$_utilsMixin_isValid(
                this.orderLine.QUAN, [rules.isInteger, rules.required, rules.positive]
            );
        },
        isRightPRICE() {
            const { rules } = this;
            return this.$_utilsMixin_isValid(
                this.orderLine.PRICE, [rules.isNumber, rules.required, rules.positive]
            );
        },
        isRightPriceWithoutVat() {
            const { rules } = this;
            return this.$_utilsMixin_isValid(
                this.orderLine.priceWithoutVat, [rules.isNumber, rules.required, rules.positive]
            );
        },
        isRightSUMMAP() {
            const { rules } = this;
            return this.$_utilsMixin_isValid(
                this.orderLine.SUMMAP, [rules.isNumber, rules.required, rules.positive]
            );
        },
        isRightSumWithoutVat() {
            const { rules } = this;
            return this.$_utilsMixin_isValid(
                this.orderLine.sumWithoutVat, [rules.isNumber, rules.required, rules.positive]
            );
        },
        savePossible() {
            return this.orderLine.GOODSCODE
                && this.isRightQUAN
                && this.isRightPRICE
                && this.isRightPriceWithoutVat
                && this.isRightSUMMAP
                && this.isRightSumWithoutVat;
        },
        options() {
            return {
                with: ['category', 'good', 'name', 'order', 'seller'],
                aggregateAttributes: [
                    'shopLinesQuantity', 'storeLinesQuantity',
                ]
            };
        }
    },
    methods: {
        async save() {
            try {
                this.loading = true;
                const orderLine = await this.$store.dispatch(
                    'ORDER-LINE/CREATE',
                    {
                        item: this.orderLine,
                        options: this.options,
                    },
                );
                this.orderLine = {
                    MASTER_ID: this.order.ID,
                    QUAN: null,
                    priceWithoutVat: null,
                    PRICE: null,
                    sumWithoutVat: null,
                    SUMMAP: null,
                };
                this.$emit('closeWithReload', orderLine.data.ID);
            } catch (e) {

            }
            this.loading = false;
        },
        changeQUAN() {
            if (this.isRightQUAN) {
                this.orderLine.sumWithoutVat = this.orderLine.QUAN * this.orderLine.priceWithoutVat;
                this.orderLine.SUMMAP = this.orderLine.QUAN * this.orderLine.PRICE;
            }
        },
        changePriceWithoutVat() {
            if (this.isRightPriceWithoutVat) {
                this.orderLine.PRICE = this.orderLine.priceWithoutVat * (1 + this.$store.getters['VAT'] / 100);
                this.changeQUAN();
            }
        },
        changePRICE() {
            if (this.isRightPRICE) {
                this.orderLine.priceWithoutVat = this.orderLine.PRICE / (1 + this.$store.getters['VAT'] / 100);
                this.changeQUAN();
            }
        },
        changeSumWithoutVat() {
            if (this.isRightSumWithoutVat) {
                this.orderLine.priceWithoutVat = this.orderLine.sumWithoutVat / this.orderLine.QUAN;
                this.changePriceWithoutVat();
            }
        },
        changeSUMMAP() {
            if (this.isRightSUMMAP) {
                this.orderLine.PRICE = this.orderLine.SUMMAP / this.orderLine.QUAN;
                this.changePRICE();
            }
        }
    },
}
</script>

<style scoped>

</style>
