<template>
    <v-dialog max-width="400px" persistent v-model="pdfDialog" @keydown.esc="$emit('close')">
        <v-card>
            <v-card-title>
                <span class="headline">Параметры счета</span>
                <v-spacer/>
                <v-btn @click="$emit('close')" icon right>
                    <v-icon color="red">
                        mdi-close
                    </v-icon>
                </v-btn>
            </v-card-title>
            <v-divider></v-divider>
            <v-card-text>
                <v-container>
                    <v-row>
                        <v-switch class="col-6"
                                  inset
                                  label="Новые реквизиты"
                                  v-model="invoiceNewAccount"
                        />
                        <v-switch :label="(invoiceWithVAT ? 'С ' : 'Без ') + 'НДС'"
                                  inset
                                  v-model="invoiceWithVAT"
                                  class="col-6"
                        />
                        <v-switch :label="invoiceWithStamp ? 'С печатью' : 'Без печати'"
                                  class="col-6"
                                  inset
                                  v-model="invoiceWithStamp"
                        />
                        <v-switch class="col-6"
                                  inset
                                  :label="invoiceBody ? 'Корпус' : 'Без корпуса'"
                                  v-model="invoiceBody"
                        />
                        <v-switch class="col-6"
                                  inset
                                  :label="invoiceProducer ? 'Производитель' : 'Без Производителя'"
                                  v-model="invoiceProducer"
                        />
                        <v-switch class="col-6"
                                  inset
                                  :label="invoiceCategory ? 'Категория' : 'Без категории'"
                                  v-model="invoiceCategory"
                        />
                        <v-switch :label="'Разделитель ' + (invoiceDivider ? '/' : '\' \'')"
                                  inset
                                  v-model="invoiceDivider"
                                  class="col-6"
                        />
                        <v-switch class="col-6"
                                  inset
                                  label="Срок поставки"
                                  v-model="invoiceDeliveryTime"
                        />
                        <v-switch class="col-6"
                                  inset
                                  label="С подвалом"
                                  v-model="invoiceWithFooter"
                        />
                        <v-btn @click="download()" class="col-2" fab>
                            <v-icon dark>mdi-download</v-icon>
                        </v-btn>
                    </v-row>
                </v-container>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script>
export default {
    name: "InvoicePdfMenu",
    props: {
        value: { type: Object },
        pdfDialog: { type: Boolean, default: false },
        sortBy: { type: Array, default: () => ['REALPRICECODE'] },
        sortDesc: { type: Array, default: () => [false] },
    },
    computed: {
        invoiceWithStamp: {
            get() {
                return this.$store.getters['AUTH/LOCAL_OPTION']('invoiceWithStamp');
            },
            set(invoiceWithStamp) {
                this.$store.commit('AUTH/SET_LOCAL_OPTION', { invoiceWithStamp });
            }
        },
        invoiceWithVAT: {
            get() {
                return this.$store.getters['AUTH/LOCAL_OPTION']('invoiceWithVAT');
            },
            set(invoiceWithVAT) {
                this.$store.commit('AUTH/SET_LOCAL_OPTION', { invoiceWithVAT });
            }
        },
        invoiceWithFooter: {
            get() {
                return this.$store.getters['AUTH/LOCAL_OPTION']('invoiceWithFooter');
            },
            set(invoiceWithFooter) {
                this.$store.commit('AUTH/SET_LOCAL_OPTION', { invoiceWithFooter });
            }
        },
        invoiceNewAccount: {
            get() {
                return this.$store.getters['AUTH/LOCAL_OPTION']('invoiceNewAccount');
            },
            set(invoiceNewAccount) {
                this.$store.commit('AUTH/SET_LOCAL_OPTION', { invoiceNewAccount });
            }
        },
        invoiceBody: {
            get() {
                return this.$store.getters['AUTH/LOCAL_OPTION']('invoiceBody');
            },
            set(invoiceBody) {
                this.$store.commit('AUTH/SET_LOCAL_OPTION', { invoiceBody });
            }
        },
        invoiceProducer: {
            get() {
                return this.$store.getters['AUTH/LOCAL_OPTION']('invoiceProducer');
            },
            set(invoiceProducer) {
                this.$store.commit('AUTH/SET_LOCAL_OPTION', { invoiceProducer });
            }
        },
        invoiceCategory: {
            get() {
                return this.$store.getters['AUTH/LOCAL_OPTION']('invoiceCategory');
            },
            set(invoiceCategory) {
                this.$store.commit('AUTH/SET_LOCAL_OPTION', { invoiceCategory });
            }
        },
        invoiceDivider: {
            get() {
                return this.$store.getters['AUTH/LOCAL_OPTION']('invoiceDivider');
            },
            set(invoiceDivider) {
                this.$store.commit('AUTH/SET_LOCAL_OPTION', { invoiceDivider });
            }
        },
        invoiceDeliveryTime: {
            get() {
                return this.$store.getters['AUTH/LOCAL_OPTION']('invoiceDeliveryTime');
            },
            set(invoiceDeliveryTime) {
                this.$store.commit('AUTH/SET_LOCAL_OPTION', { invoiceDeliveryTime });
            }
        },
    },
    methods: {
        download() {
            this.$emit('downloading', true);
            this.$emit('close');
            const
                withVAT = this.invoiceWithVAT,
                withStamp = this.invoiceWithStamp,
                withFooter = this.invoiceWithFooter,
                newAccount = this.invoiceNewAccount,
                body = this.invoiceBody,
                producer = this.invoiceProducer,
                category = this.invoiceCategory,
                divider = this.invoiceDivider,
                deliveryTime = this.invoiceDeliveryTime,
                sortBy = this.sortBy,
                sortDesc = this.sortDesc;
            this.$store.dispatch(
                'INVOICE/PDF',
                {
                    id: this.value.SCODE,
                    query: {
                        withVAT,
                        withStamp,
                        withFooter,
                        newAccount,
                        body,
                        producer,
                        category,
                        divider,
                        deliveryTime,
                        sortBy,
                        sortDesc,
                    }
                }
            )
                .then(() => {
                })
                .catch(() => {
                })
                .then(() => {
                    this.$emit('downloading', false);
                });
        }
    }

}
</script>

<style scoped>

</style>
