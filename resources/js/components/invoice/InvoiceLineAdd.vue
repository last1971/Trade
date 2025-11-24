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
                    <v-combobox
                        :rules="[rules.isNumber, rules.required, rules.positive]"
                        v-model="invoiceLine.PRICE"
                        :items="pricesForChoose"
                        label="Цена с НДС"
                        @input="changePRICE"
                    ></v-combobox>
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
                    <delivery-time-select v-model="invoiceLine.PRIM" />
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
        <v-dialog
            v-if="invoiceLineAlready"
            v-model="isGoodAlready"
            persistent
            max-width="800"
        >
            <v-card>
                <v-card-title>
                    В счете уже
                    {{ invoiceLineAlready.QUAN }}
                    {{ invoiceLineAlready.good.UNIT_I }}
                    {{ invoiceLineAlready.name.NAME }}
                    по
                    {{ invoiceLineAlready.PRICE | formatRub }}
                    за
                    {{ invoiceLineAlready.PRIM }}
                </v-card-title>

                <v-card-actions>
                    <v-spacer></v-spacer>

                    <v-btn
                        color="red darken-1"
                        text
                        @click="changeGood"
                    >
                        Будем менять
                    </v-btn>

                    <v-btn
                        color="green darken-1"
                        text
                        @click="notChangeGood"
                    >
                        Оставить как есть
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-card>
</template>

<script>
import GoodSelect from "../good/GoodSelect";
import utilsMixin from "../../mixins/utilsMixin";
import DeliveryTimeSelect from "../DeliveryTimeSelect";
import getVAT from "../../helpers/vatHelper";
export default {
    name: "InvoiceLineAdd",
    components: {DeliveryTimeSelect, GoodSelect},
    mixins:[utilsMixin],
    props: {
        invoice: { type: Object, required: true },
        newInvoiceLine: { type: Object, default: () => {} },
        pricesForChoose: { type: Array, defult: () => [] },
    },
    data() {
        return {
            invoiceLine: {
                SCODE: this.invoice.SCODE,
                GOODSCODE: null,
                QUAN: null,
                priceWithoutVat: null,
                PRICE: null,
                sumWithoutVat: null,
                SUMMAP: null,
                PRIM: null,
            },
            loading: false,
            goodId: null,
            isGoodAlready: false,
            invoiceLineAlready: null,
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
    watch: {
        newInvoiceLine: {
            deep: true,
            immediate: true,
            handler() {
                if (!_.isEmpty(this.newInvoiceLine)) {
                    const { deliveryTime, goodId, orderQuantity, sellerId } = this.newInvoiceLine;
                    this.invoiceLine.PRIM = deliveryTime
                        ? this.formatDays(deliveryTime)
                        : deliveryTime === 0 ? 'склад' : null;
                    this.invoiceLine.QUAN = orderQuantity;
                    this.invoiceLine.GOODSCODE = goodId;
                    this.invoiceLine.WHERE_ORDERED = _.find(this.$store.getters['SELLER-PRICE/SELLERS'], { sellerId }).name;
                }
                if (!_.isEmpty(this.pricesForChoose)) {
                    this.invoiceLine.PRICE = _.max(this.pricesForChoose);
                    this.changePRICE();
                }
            }
        },
        invoiceLine: {
            deep: true,
            immediate: true,
            async handler() {
                if (this.goodId !== this.invoiceLine.GOODSCODE && this.invoiceLine.GOODSCODE) {
                    this.loading = true;
                    try {
                        const response = await this.$store.dispatch(
                            'INVOICE-LINE/ALL',
                            {
                                with: ['category', 'good', 'name'],
                                aggregateAttributes: [
                                    'reservesQuantity',
                                    'pickUpsQuantity',
                                    'transferOutLinesQuantity',
                                    'orderLinesTransitQuantity',
                                    'storeLinesTransitQuantity',
                                ],
                                filterAttributes: ['REALPRICE.GOODSCODE', 'REALPRICE.SCODE'],
                                filterOperators: ['=', '='],
                                filterValues: [this.invoiceLine.GOODSCODE, this.invoiceLine.SCODE],
                            }
                        );
                        if (response.total > 0) {
                            this.invoiceLineAlready = response.copyItems[0];
                            this.isGoodAlready = true;
                        }
                    } catch (e) {

                    }
                    this.loading = false;
                } else {
                    this.goodId = this.invoiceLine.GOODSCODE;
                }
            }
        }
    },
    methods: {
        async save() {
            try {
                this.loading = true;
                const invoiceLine = await this.$store.dispatch(
                    this.invoiceLine.REALPRICECODE ? 'INVOICE-LINE/UPDATE' : 'INVOICE-LINE/CREATE',
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
                this.invoiceLine.PRICE = this.invoiceLine.priceWithoutVat * (1 + getVAT(this.invoice.DATA) / 100);
                this.changeQUAN();
            }
        },
        changePRICE() {
            if (this.isRightPRICE) {
                this.invoiceLine.priceWithoutVat = this.invoiceLine.PRICE / (1 + getVAT(this.invoice.DATA) / 100);
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
        },
        changeGood() {
            this.goodId = this.invoiceLine.GOODSCODE;
            this.invoiceLine.REALPRICECODE = this.invoiceLineAlready.REALPRICECODE;
            this.isGoodAlready = false;
        },
        notChangeGood() {
            this.invoiceLine.GOODSCODE = this.goodId;
            this.isGoodAlready = false;
        }
    },
}
</script>

<style scoped>

</style>
