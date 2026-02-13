<template>
    <v-dialog max-width="500px"
              persistent
              v-model="pdfDialog"
              @keydown.esc="$emit('close')"
    >
        <v-card>
            <v-card-title>
                <span class="headline">Параметры УПД</span>
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
                    </v-row>
                    <v-divider class="my-2"></v-divider>
                    <v-row>
                        <v-switch class="col-12"
                                  inset
                                  label="Альтернативное основание"
                                  v-model="useAltBasis"
                        />
                    </v-row>
                    <v-row v-if="useAltBasis">
                        <v-text-field class="col-12"
                                      label="Тип документа"
                                      v-model="basis"
                                      placeholder="Договор"
                                      dense
                        />
                        <v-text-field class="col-6"
                                      label="Номер"
                                      v-model="basisNumber"
                                      dense
                        />
                        <v-menu
                            :close-on-content-click="false"
                            :nudge-right="40"
                            min-width="290px"
                            offset-y
                            transition="scale-transition"
                            v-model="basisDatePicker"
                        >
                            <template v-slot:activator="{ on }">
                                <v-text-field
                                    class="col-6"
                                    :value="basisDate"
                                    label="Дата"
                                    prepend-icon="mdi-calendar"
                                    readonly
                                    dense
                                    v-on="on"
                                />
                            </template>
                            <v-date-picker @input="basisDatePicker = false" v-model="basisDateRaw"></v-date-picker>
                        </v-menu>
                    </v-row>
                    <v-divider class="my-2"></v-divider>
                    <v-row>
                        <v-switch class="col-12"
                                  inset
                                  label="Авансовая СФ"
                                  v-model="useAdvanceInvoice"
                        />
                    </v-row>
                    <template v-if="useAdvanceInvoice">
                        <v-row v-for="(ai, index) in advanceInvoices" :key="index" align="center">
                            <v-text-field class="col-4"
                                          label="Номер СФ"
                                          v-model="ai.number"
                                          dense
                            />
                            <v-menu
                                :close-on-content-click="false"
                                :nudge-right="40"
                                min-width="290px"
                                offset-y
                                transition="scale-transition"
                                v-model="ai.datePicker"
                            >
                                <template v-slot:activator="{ on }">
                                    <v-text-field
                                        class="col-5"
                                        :value="formatDate(ai.dateRaw)"
                                        label="Дата СФ"
                                        prepend-icon="mdi-calendar"
                                        readonly
                                        dense
                                        v-on="on"
                                    />
                                </template>
                                <v-date-picker @input="ai.datePicker = false" v-model="ai.dateRaw"></v-date-picker>
                            </v-menu>
                            <v-btn icon small @click="removeAdvanceInvoice(index)" v-if="advanceInvoices.length > 1">
                                <v-icon color="red" small>mdi-close</v-icon>
                            </v-btn>
                        </v-row>
                        <v-row class="mb-2">
                            <v-btn text small color="primary" @click="addAdvanceInvoice">
                                <v-icon left small>mdi-plus</v-icon>
                                Добавить
                            </v-btn>
                        </v-row>
                    </template>
                    <v-divider class="my-2"></v-divider>
                    <v-row justify="center">
                        <v-btn @click="download('pdf')" class="mx-2" fab>
                            <v-icon color="red">mdi-adobe-acrobat</v-icon>
                        </v-btn>
                        <v-btn @click="download('xml')" class="mx-2" fab>
                            <v-icon color="blue">mdi-xml</v-icon>
                        </v-btn>
                    </v-row>
                </v-container>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script>
import moment from 'moment';

export default {
    name: "TransferOutPdfMenu",
    props: {
        value: { type: Object },
        pdfDialog: { type: Boolean, default: false },
    },
    data() {
        return {
            useAltBasis: false,
            basis: '',
            basisNumber: '',
            basisDateRaw: null,
            basisDatePicker: false,
            useAdvanceInvoice: false,
            advanceInvoices: [{ number: '', dateRaw: null, datePicker: false }],
        }
    },
    computed: {
        body: {
            get() {
                return this.$store.getters['AUTH/LOCAL_OPTION']('transferOutBody');
            },
            set(transferOutBody) {
                this.$store.commit('AUTH/SET_LOCAL_OPTION', { transferOutBody });
            },
        },
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
        basisDate() {
            return this.basisDateRaw ? moment(this.basisDateRaw).format('DD.MM.YYYY') : '';
        },
    },
    watch: {
        downloading() {
            this.$emit('downloading', this.downloading);
        },
    },
    methods: {
        formatDate(dateRaw) {
            return dateRaw ? moment(dateRaw).format('DD.MM.YYYY') : '';
        },
        addAdvanceInvoice() {
            this.advanceInvoices.push({ number: '', dateRaw: null, datePicker: false });
        },
        removeAdvanceInvoice(index) {
            this.advanceInvoices.splice(index, 1);
        },
        download(type) {
            this.$emit('downloading', true);
            this.$emit('close');
            const {body, producer, category, divider} = this;
            const query = {body, producer, category, divider};

            if (this.useAltBasis && this.basis) {
                query.basis = this.basis;
                query.basisNumber = this.basisNumber;
                query.basisDate = this.basisDate;
            }

            if (this.useAdvanceInvoice) {
                const filtered = this.advanceInvoices
                    .filter(ai => ai.number)
                    .map(ai => ({ number: ai.number, date: this.formatDate(ai.dateRaw) }));
                if (filtered.length) {
                    query.advanceInvoices = JSON.stringify(filtered);
                }
            }

            const action = type === 'xml' ? 'TRANSFER-OUT/XML' : 'TRANSFER-OUT/PDF';
            const download = this.$store.dispatch(action, { id: this.value.SFCODE, query });

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
