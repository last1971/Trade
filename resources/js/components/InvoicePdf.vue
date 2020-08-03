<template>
    <div>
        <v-btn :loading="downloading" @click="pdfDialog=true" icon>
            <v-icon color="red">mdi-adobe-acrobat</v-icon>
        </v-btn>
        <v-dialog max-width="400px" persistent v-model="pdfDialog">
            <v-card>
                <v-card-title>
                    <span class="headline">Параметры счета</span>
                    <v-spacer/>
                    <v-btn @click="pdfDialog=false" icon right>
                        <v-icon color="red">
                            mdi-close
                        </v-icon>
                    </v-btn>
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text>
                    <v-container>
                        <v-row>
                            <v-switch class="ml-4"
                                      inset
                                      label="Новые реквизиты"
                                      v-model="newAccount"
                            />
                            <v-switch :label="(withVAT ? 'С ' : 'Без ') + 'НДС'"
                                      inset
                                      v-model="withVAT"
                                      class="ml-4"
                            />
                            <v-switch :label="withStamp ? 'С печатью' : 'Без печати'"
                                      class="ml-4"
                                      inset
                                      v-model="withStamp"
                            />
                            <v-switch class="ml-4"
                                      inset
                                      label="Корпус"
                                      v-model="body"
                            />
                            <v-switch class="ml-4"
                                      inset
                                      label="Производитель"
                                      v-model="producer"
                            />
                            <v-switch class="ml-4"
                                      inset
                                      label="Категория"
                                      v-model="category"
                            />
                            <v-switch :label="'Разделитель ' + (divider ? '/' : '\' \'')"
                                      inset
                                      v-model="divider"
                                      class="ml-4"
                            />
                            <v-switch class="ml-4"
                                      inset
                                      label="Срок поставки"
                                      v-model="deliveryTime"
                            />
                            <v-btn @click="download()" class="ml-4 " fab>
                                <v-icon dark>mdi-download</v-icon>
                            </v-btn>
                        </v-row>
                    </v-container>
                </v-card-text>
            </v-card>
        </v-dialog>
    </div>
</template>

<script>
    export default {
        name: "InvoicePdf",
        props: ['value'],
        data() {
            return {
                pdfDialog: false,
                downloading: false,
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
            downloading() {
                this.$emit('downloading', this.downloading);
            },
        },
        methods: {
            download() {
                this.downloading = true;
                this.pdfDialog = false;
                const {withVAT, withStamp, newAccount, body, producer, category, divider, deliveryTime} = this;
                const download = this.$store.dispatch(
                    'INVOICE/PDF',
                    {
                        id: this.value.SCODE,
                        query: {withVAT, withStamp, newAccount, body, producer, category, divider, deliveryTime}
                    }
                );
                download
                    .then(() => {
                    })
                    .catch(() => {
                    })
                    .then(() => {
                        this.downloading = false
                    });
            }
        }
    }
</script>

<style scoped>

</style>
