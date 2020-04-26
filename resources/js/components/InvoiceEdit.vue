<template>
    <v-form class="mx-2">
        <v-row>
            <v-col cols="12" sm="auto">
                <v-menu
                    :close-on-content-click="false"
                    :nudge-right="40"
                    min-width="290px"
                    offset-y
                    transition="scale-transition"
                    v-model="datePicker"
                >
                    <template v-slot:activator="{ on }">
                        <v-text-field
                            :disabled="notEditable || notCan"
                            :value="model.DATA | formatDate"
                            label="Дата"
                            prepend-icon="mdi-calendar-edit"
                            readonly
                            v-on="on"
                        />
                    </template>
                    <v-date-picker @input="datePicker = false" first-day-of-week="1" v-model="model.DATA">

                    </v-date-picker>
                </v-menu>
            </v-col>
            <v-col cols="12" sm="auto">
                <v-text-field :disabled="notEditable || notCan"
                              :rules="[rules.required, rules.isInteger]"
                              label="Номер"
                              v-model="model.NS"
                />
            </v-col>
            <v-col cols="12" sm="auto">
                <buyer-select :disabled="notEditable || notCan" v-model="model.POKUPATCODE"/>
            </v-col>
            <v-col cols="12" sm="auto">
                <firm-select :disabled="notEditable || notCan" v-model="model.FIRM_ID"/>
            </v-col>
            <v-col cols="12" sm="auto">
                <v-text-field :disabled="true" :value="model.invoiceLinesSum | formatRub" label="Сумма"/>
            </v-col>
            <v-col cols="12" sm="auto">
                <v-text-field :disabled="true" :value="model.cashFlowsSum | formatRub" label="Оплачено"/>
            </v-col>
            <v-col cols="12" sm="auto">
                <v-text-field :disabled="true" :value="model.transferOutLinesSum | formatRub" label="Отгружено"/>
            </v-col>
            <v-col cols="12" sm="auto" v-if="!notCan">
                <v-text-field label="Примечание" v-model="model.PRIM"/>
            </v-col>
            <v-col cols="12" sm="auto">
                <invoice-status-select :disabled="notCan" v-model="model.STATUS"/>
            </v-col>
            <v-col cols="12" sm="auto" v-if="!notCan">
                <v-btn :block="!$vuetify.breakpoint.smAndUp"
                       :disabled="savePossible"
                       :fab="$vuetify.breakpoint.smAndUp"
                       :loading="loading"
                       @click="save"
                       class="mt-2"
                >
                    <v-icon v-if="$vuetify.breakpoint.smAndUp">mdi-content-save</v-icon>
                    <span v-else>Сохранить</span>
                </v-btn>
            </v-col>
            <v-col cols="12" sm="auto" v-if="$vuetify.breakpoint.smAndUp">
                <v-speed-dial :open-on-hover="true" direction="bottom">
                    <template v-slot:activator>
                        <v-btn :loading="downloading" class="mt-2" fab icon>
                            <v-icon>mdi-download</v-icon>
                        </v-btn>
                    </template>
                    <v-btn @click="download('pdf')" fab>
                        <v-icon color="red">mdi-adobe-acrobat</v-icon>
                    </v-btn>
                    <v-btn @click="download('xlsx')" fab>
                        <v-icon color="green">mdi-microsoft-excel</v-icon>
                    </v-btn>
                </v-speed-dial>
            </v-col>
        </v-row>
    </v-form>
</template>

<script>
    import BuyerSelect from "./BuyerSelect";
    import utilsMixin from "../mixins/utilsMixin";
    import InvoiceStatusSelect from "./InvoiceStatusSelect";
    import FirmSelect from "./FirmSelect";
    import editMixin from "../mixins/editMixin";

    export default {
        name: "InvoiceEdit",
        components: {FirmSelect, InvoiceStatusSelect, BuyerSelect},
        mixins: [editMixin, utilsMixin],
        data() {
            return {
                MODEL: 'INVOICE',
                downloading: false,
            }
        },
        computed: {
            notEditable() {
                return this.model.transferOutLinesSum > 0;
            },
            savePossible() {
                const a = _.pick(_.omit(this.value, ['DATA']), this.fillable);
                const b = _.pick(_.omit(this.model, ['DATA']), this.fillable);
                const d = this.value.DATA ? this.value.DATA.substr(0, 10) : undefined;
                return _.isEqual(a, b) && this.model.DATA === d;
            },
            notCan() {
                return !this.$store.getters['AUTH/HAS_PERMISSION']('invoice.update');
            }
        },
        methods: {
            download(type) {
                this.downloading = true;
                const download = type === 'pdf'
                    ? this.$store.dispatch('INVOICE/PDF', this.value.SCODE)
                    : this.$store.dispatch('INVOICE-LINE/SAVE', {
                        with: ['category', 'good', 'name'],
                        filterAttributes: [
                            'invoice.SCODE',
                        ],
                        filterOperators: ['='],
                        filterValues: [this.value.SCODE],
                        sortBy: ['category.CATEGORY', 'name.NAME'],
                        sortDesc: [false, false],
                    });
                download
                    .then(() => {
                    })
                    .catch(() => {
                    })
                    .then(() => this.downloading = false);
            }
        }
    }
</script>

<style scoped>

</style>
