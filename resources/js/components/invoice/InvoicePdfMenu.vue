<template>
    <v-dialog max-width="400px" persistent v-model="pdfDialog">
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
                                  v-model="newAccount"
                        />
                        <v-switch :label="(withVAT ? 'С ' : 'Без ') + 'НДС'"
                                  inset
                                  v-model="withVAT"
                                  class="col-6"
                        />
                        <v-switch :label="withStamp ? 'С печатью' : 'Без печати'"
                                  class="col-6"
                                  inset
                                  v-model="withStamp"
                        />
                        <v-switch class="col-6"
                                  inset
                                  :label="body ? 'Корпус' : 'Без корпуса'"
                                  v-model="body"
                        />
                        <v-switch class="col-6"
                                  inset
                                  :label="producer ? 'Производитель' : 'Без Производителя'"
                                  v-model="producer"
                        />
                        <v-switch class="col-6"
                                  inset
                                  :label="category ? 'Категория' : 'Без категории'"
                                  v-model="category"
                        />
                        <v-switch :label="'Разделитель ' + (divider ? '/' : '\' \'')"
                                  inset
                                  v-model="divider"
                                  class="col-6"
                        />
                        <v-switch class="col-6"
                                  inset
                                  label="Срок поставки"
                                  v-model="deliveryTime"
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
    data() {
        return {
            withStamp: true,
            withVAT: true,
            newAccount: false,
            body: true,
            producer: true,
            category: true,
            divider: true,
            deliveryTime: true,
        }
    },
    created() {
        this.withStamp = this.$store.getters['AUTH/LOCAL_OPTION']('withStamp');
        this.withVAT = this.$store.getters['AUTH/LOCAL_OPTION']('withVAT');
    },
    watch: {
        withStamp(val) {
            this.$store.commit('AUTH/SET_LOCAL_OPTION', {withStamp: val});
        },
        withVAT(val) {
            this.$store.commit('AUTH/SET_LOCAL_OPTION', {withVAT: val});
        },
    },
    methods: {
        download() {
            this.$emit('downloading', true);
            this.$emit('close');
            const
                { withVAT, withStamp, newAccount, body, producer, category, divider, deliveryTime, sortBy, sortDesc }
                = this;
            this.$store.dispatch(
                'INVOICE/PDF',
                {
                    id: this.value.SCODE,
                    query: {
                        withVAT,
                        withStamp,
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
