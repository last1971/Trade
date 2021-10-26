<template>
    <v-dialog max-width="400px" persistent v-model="pdfDialog" @keydown.esc="$emit('close')">
        <v-card>
            <v-card-title>
                <span class="headline">Параметры счет-фактуры</span>
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
    name: "TransferOutPdfMenu",
    props: {
        value: { type: Object },
        pdfDialog: { type: Boolean, default: false },
    },
    computed: {
        body: {
            get() {
                return this.$store.getters['AUTH/LOCAL_OPTION']('transferOutBody');
            },
            set(transferOutBody) {
                this.$store.commit('AUTH/SET_LOCAL_OPTION', { transferOutBody });
            },
        }   ,
        producer: {
            get() {
                return this.$store.getters['AUTH/LOCAL_OPTION']('transferOutProducer');
            },
            set(transferOutProducer) {
                this.$store.commit('AUTH/SET_LOCAL_OPTION', { transferOutProducer });
            },
        },
        category: {
            get() {
                return this.$store.getters['AUTH/LOCAL_OPTION']('transferOutCategory');
            },
            set(transferOutCategory) {
                this.$store.commit('AUTH/SET_LOCAL_OPTION', { transferOutCategory });
            },
        },
        divider: {
            get() {
                return this.$store.getters['AUTH/LOCAL_OPTION']('transferOutDivider');
            },
            set(transferOutDivider) {
                this.$store.commit('AUTH/SET_LOCAL_OPTION', { transferOutDivider });
            },
        },
    },
    watch: {
        downloading() {
            this.$emit('downloading', this.downloading);
        },
    },
    methods: {
        download() {
            this.$emit('downloading', true);
            this.$emit('close');
            const {body, producer, category, divider} = this;
            const download = this.$store.dispatch(
                'TRANSFER-OUT/PDF',
                { id: this.value.SFCODE, query: {body, producer, category, divider} }
            );
            download
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
