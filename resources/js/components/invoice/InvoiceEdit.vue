<template>
    <v-form class="mx-2">
        <v-row>
            <v-col cols="12" sm="auto">
                <date-picker v-model="model.DATA" label="Дата" :disabled="notEditable || notCan"/>
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
                <v-text-field :disabled="notEditable || notCan"
                              :rules="[rules.isInteger]"
                              label="Заявка"
                              v-model="model.NZ"
                />
            </v-col>
            <v-col cols="12" sm="auto">
                <firm-history-select
                    :disabled="notEditable || notCan"
                    v-model="model.FIRMS_HISTORY_ID"
                    :firm-id="model.FIRM_ID"
                    :can-empty="true"
                />
            </v-col>
            <v-col cols="12" sm="auto">
                <v-text-field :disabled="true" :value="model.invoiceLinesSum | formatRub" label="Сумма"/>
            </v-col>
            <v-col cols="12" sm="auto">
                <cash-flows-modal v-model="model"
                                  :text="model.cashFlowsSum"
                                  x-large
                />
            </v-col>
            <v-col cols="12" sm="auto">
                <v-text-field :disabled="true" :value="model.transferOutLinesSum | formatRub" label="Отгружено"/>
            </v-col>
            <v-col cols="12" sm="auto" v-if="!notCan">
                <v-text-field label="Примечание" v-model="model.PRIM"/>
            </v-col>
            <v-col cols="12" sm="auto" v-if="!notCan">
                <v-text-field label="ИГК" v-model="model.IGK"/>
            </v-col>
            <v-col cols="12" sm="auto">
                <invoice-status-select :disabled="notCan" v-model="model.STATUS"/>
            </v-col>
            <v-col cols="12" sm="auto">
                <employee-select v-model="model.STAFF_ID" :can-empty="true" :disabled="notCanEmployeeEdit"/>
            </v-col>
            <v-col cols="12" sm="auto" v-if="!notCan">
                <v-btn :block="!$vuetify.breakpoint.smAndUp"
                       :disabled="savePossible"
                       :fab="$vuetify.breakpoint.smAndUp"
                       :loading="loading"
                       @click="save"
                       class="mt-2"
                >
                    <v-icon v-if="$vuetify.breakpoint.smAndUp" color="green">mdi-content-save</v-icon>
                    <span v-else>Сохранить</span>
                </v-btn>
                <v-btn v-if="$vuetify.breakpoint.smAndUp"
                       fab
                       class="mt-2 ml-2"
                       @click="addInvoiceLine = true"
                       :disabled="notEditable || !model.SCODE || value.STATUS > 0"
                >
                    <v-icon color="primary">mdi-playlist-plus</v-icon>
                </v-btn>
                <v-btn v-if="$vuetify.breakpoint.smAndUp"
                       fab
                       class="mt-2 ml-2"
                       @click="transferOut"
                       :disabled="value.STATUS < 3 ||value.STATUS > 4"
                       :loading="creatingTransferOut"
                >
                    <v-icon color="accent">mdi-clipboard-text-play</v-icon>
                </v-btn>
            </v-col>
            <v-col cols="12" sm="auto" v-if="$vuetify.breakpoint.smAndUp">
                <v-speed-dial :open-on-hover="true" direction="bottom">
                    <template v-slot:activator>
                        <v-btn :loading="downloading" class="mt-2" fab icon>
                            <v-icon>mdi-download</v-icon>
                        </v-btn>
                    </template>
                    <v-btn @click="pdfDialog=true" fab>
                        <v-icon color="red">mdi-adobe-acrobat</v-icon>
                    </v-btn>
                    <v-btn @click="download('xlsx')" fab>
                        <v-icon color="green">mdi-microsoft-excel</v-icon>
                    </v-btn>
                    <v-btn @click="receipt" fab v-if="!notCan">
                        <v-icon color="primary">mdi-paper-roll</v-icon>
                    </v-btn>
                </v-speed-dial>
                <invoice-pdf-menu
                    v-model="value"
                    :pdf-dialog="pdfDialog"
                    @close="pdfDialog=false"
                    @downloading="setDownloading"
                    :sort-by="sortBy"
                    :sort-desc="sortDesc"
                />
            </v-col>
        </v-row>
        <v-dialog v-model="addInvoiceLine">
            <invoice-line-add :invoice="model"
                              @close="addInvoiceLine=false"
                              @closeWithReload="closeWithReload"
            />
        </v-dialog>
    </v-form>
</template>

<script>
import BuyerSelect from "../BuyerSelect";
import utilsMixin from "../../mixins/utilsMixin";
import InvoiceStatusSelect from "./InvoiceStatusSelect";
import FirmSelect from "../FirmSelect";
import editMixin from "../../mixins/editMixin";
import InvoicePdf from "./InvoicePdf";
import InvoicePdfMenu from "./InvoicePdfMenu";
import InvoiceLineAdd from "./InvoiceLineAdd";
import FirmHistorySelect from "../FirmHistorySelect";
import DatePicker from "../DatePicker";
import EmployeeSelect from "../EmployeeSelect";
import {mapGetters} from "vuex";
import CashFlowsModal from "../CashFlowsModal.vue";

export default {
    name: "InvoiceEdit",
    components: {
        CashFlowsModal,
        EmployeeSelect,
        DatePicker,
        FirmHistorySelect,
        InvoiceLineAdd, InvoicePdfMenu, InvoicePdf, FirmSelect, InvoiceStatusSelect, BuyerSelect},
    mixins: [editMixin, utilsMixin],
    props: {
        sortBy: { type: Array, default: () => [] },
        sortDesc: { type: Array, default: () => [] },
    },
    data() {
        return {
            MODEL: 'INVOICE',
            downloading: false,
            pdfDialog: false,
            addInvoiceLine: false,
            firmId: undefined,
            creatingTransferOut: false,
        }
    },
    computed: {
        ...mapGetters({ isAdmin: 'AUTH/IS_ADMIN' }),
        notEditable() {
            return this.model.transferOutLinesSum > 0;
        },
        savePossible() {
            //const a = _.pick(_.omit(this.value, ['DATA']), this.fillable);
            //const b = _.pick(_.omit(this.model, ['DATA']), this.fillable);
            //const d = this.value.DATA ? this.value.DATA.substr(0, 10) : undefined;
            return _.isEqual(this.value, this.model); //&& this.model.DATA === d;
        },
        notCan() {
            return !this.$store.getters['AUTH/HAS_PERMISSION']('invoice.update');
        },
        notCanEmployeeEdit() {
            return !this.$store.getters['AUTH/HAS_PERMISSION']('invoice.employee');
        },
    },
    watch: {
        model: {
            deep: true,
            handler(v) {
                if (this.firmId === undefined) {
                    this.firmId = v.FIRM_ID;
                } else if (this.firmId !== v.FIRM_ID) {
                    v.firmId = v.FIRM_ID;
                    v.FIRMS_HISTORY_ID = null;
                }
            }
        },
    },
    methods: {
        async transferOut() {
            try {
                this.creatingTransferOut = true;
                const transferOut = await this.$store.dispatch('TRANSFER-OUT/CREATE', {
                    item: { SCODE: this.value.SCODE },
                    options: {
                        with: ['buyer', 'employee', 'firm', 'invoice'],
                        aggregateAttributes: [
                            'transferOutLinesCount', 'transferOutLinesSum'
                        ],
                    }
                });
                this.$router.push({ name: 'transfer-out', params: { id: transferOut.data.SFCODE } });
            }  catch (e) {

            }
            this.creatingTransferOut = false;
        },
        async receipt() {
            this.downloading = true;
            await this.$store.dispatch('INVOICE/RECEIPT', this.value.SCODE)
            this.downloading = false;
        },
        download(type) {
            this.downloading = true;
            this.pdfDialog = false;
            const {withVAT, withStamp, newAccount} = this;
            const download = type === 'pdf'
                ? this.$store.dispatch(
                    'INVOICE/PDF', {id: this.value.SCODE, query: {withVAT, withStamp, newAccount}}
                )
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
        },
        setDownloading(downloading) {
            this.downloading = downloading
        },
        closeWithReload(id) {
            this.addInvoiceLine = false;
            this.$emit('newInvoiceLine', id);
            this.$emit('reloadInvoice');
        }
    },
}
</script>

<style scoped>

</style>
