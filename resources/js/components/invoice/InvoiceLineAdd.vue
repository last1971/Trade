<template>
    <v-card>
        <v-card-title class="headline">
            <span class="headline">Строка счёта</span>
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
                    <good-select v-model="invoiceLine.GOODSCODE"/>
                </v-col>
            </v-row>
            <v-row>
                <v-col>
                    <v-text-field
                        :rules="[rules.isInteger, rules.required, rules.positive]"
                        v-model="invoiceLine.QUAN"
                        label="Количество"
                        @input="changeQUAN"
                    />
                </v-col>
                <v-col>
                    <v-text-field
                        :rules="[rules.isNumber, rules.required, rules.positive]"
                        label="Цена без НДС"
                        v-model="invoiceLine.priceWithoutVat"
                        @input="changePriceWithoutVat"
                    />
                </v-col>
                <v-col>
                    <v-text-field
                        :rules="[rules.isNumber, rules.required, rules.positive]"
                        label="Цена с НДС"
                        v-model="invoiceLine.PRICE"
                        @input="changePRICE"
                    />
                </v-col>
                <v-col>
                    <v-text-field
                        :rules="[rules.isNumber, rules.required, rules.positive]"
                        label="Сумма без НДС"
                        v-model="invoiceLine.sumWithoutVat"
                        @input="changeSumWithoutVat"
                    />
                </v-col>
                <v-col>
                    <v-text-field
                        :rules="[rules.isNumber, rules.required, rules.positive]"
                        label="Сумма с НДС"
                        v-model="invoiceLine.SUMMAP"
                        @input="changeSUMMAP"
                    />
                </v-col>
            </v-row>
            <v-row>
                <v-col>
                    <v-text-field
                        label="Срок"
                        v-model="invoiceLine.PRIM"
                    />
                </v-col>
                <v-col>
                    <v-text-field
                        label="Откуда"
                        v-model="invoiceLine.WHERE_ORDERED"
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
import GoodSelect from "../good/GoodSelect";
import utilsMixin from "../../mixins/utilsMixin";
export default {
    name: "InvoiceLineAdd",
    components: {GoodSelect},
    mixins:[utilsMixin],
    props: {
        invoice: { type: Object, required: true },
    },
    data() {
        return {
            invoiceLine: {
                SCODE: this.invoice.SCODE,
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
                this.invoiceLine.QUAN, [rules.isInteger, rules.required, rules.positive]
            );
        },
        isRightPRICE() {
            const { rules } = this;
            return this.$_utilsMixin_isValid(
                this.invoiceLine.PRICE, [rules.isNumber, rules.required, rules.positive]
            );
        },
        isRightPriceWithoutVat() {
            const { rules } = this;
            return this.$_utilsMixin_isValid(
                this.invoiceLine.priceWithoutVat, [rules.isNumber, rules.required, rules.positive]
            );
        },
        isRightSUMMAP() {
            const { rules } = this;
            return this.$_utilsMixin_isValid(
                this.invoiceLine.SUMMAP, [rules.isNumber, rules.required, rules.positive]
            );
        },
        isRightSumWithoutVat() {
            const { rules } = this;
            return this.$_utilsMixin_isValid(
                this.invoiceLine.sumWithoutVat, [rules.isNumber, rules.required, rules.positive]
            );
        },
        savePossible() {
            return this.invoiceLine.GOODSCODE
                && this.isRightQUAN
                && this.isRightPRICE
                && this.isRightPriceWithoutVat
                && this.isRightSUMMAP
                && this.isRightSumWithoutVat;
        },
        options() {
            return {
                with: ['category', 'good', 'name'],
                aggregateAttributes: [
                    'reservesQuantity', 'pickUpsQuantity', 'transferOutLinesQuantity'
                ],
            };
        }
    },
    methods: {
        async save() {
            try {
                this.loading = true;
                const invoiceLine = await this.$store.dispatch(
                    'INVOICE-LINE/CREATE',
                    {
                        item: this.invoiceLine,
                        options: this.options,
                    },
                );
                this.invoiceLine = {
                    SCODE: this.invoice.SCODE,
                    QUAN: null,
                    priceWithoutVat: null,
                    PRICE: null,
                    sumWithoutVat: null,
                    SUMMAP: null,
                };
                this.$emit('closeWithReload', invoiceLine.data.REALPRICECODE);
            } catch (e) {

            }
            this.loading = false;
        },
        changeQUAN() {
            if (this.isRightQUAN) {
                this.invoiceLine.sumWithoutVat = this.invoiceLine.QUAN * this.invoiceLine.priceWithoutVat;
                this.invoiceLine.SUMMAP = this.invoiceLine.QUAN * this.invoiceLine.PRICE;
            }
        },
        changePriceWithoutVat() {
            if (this.isRightPriceWithoutVat) {
                this.invoiceLine.PRICE = this.invoiceLine.priceWithoutVat * (1 + this.$store.getters['VAT'] / 100);
                this.changeQUAN();
            }
        },
        changePRICE() {
            if (this.isRightPRICE) {
                this.invoiceLine.priceWithoutVat = this.invoiceLine.PRICE / (1 + this.$store.getters['VAT'] / 100);
                this.changeQUAN();
            }
        },
        changeSumWithoutVat() {
            if (this.isRightSumWithoutVat) {
                this.invoiceLine.priceWithoutVat = this.invoiceLine.sumWithoutVat / this.invoiceLine.QUAN;
                this.changePriceWithoutVat();
            }
        },
        changeSUMMAP() {
            if (this.isRightSUMMAP) {
                this.invoiceLine.PRICE = this.invoiceLine.SUMMAP / this.invoiceLine.QUAN;
                this.changePRICE();
            }
        }
    },
}
</script>

<style scoped>

</style>