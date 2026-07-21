<template>
    <v-form class="mx-2">
        <v-row>
            <v-col cols="12" sm="auto">
                <date-picker
                    v-model="model.DATA"
                    label="Дата"
                    :disabled="notEditable || notCan"
                />
            </v-col>
            <v-col cols="12" sm="auto">
                <v-text-field
                    :disabled="notEditable || notCan"
                    :rules="[rules.required, rules.isInteger]"
                    label="Номер"
                    v-model="model.NS"
                />
            </v-col>
            <v-col cols="12" sm="auto">
                <buyer-select
                    :disabled="notEditable || notCan"
                    v-model="model.POKUPATCODE"
                />
            </v-col>
            <v-col cols="12" sm="auto">
                <firm-select
                    :disabled="notEditable || notCan"
                    v-model="model.FIRM_ID"
                />
            </v-col>
            <v-col cols="12" sm="auto">
                <v-text-field
                    :disabled="notEditable || notCan"
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
                <v-text-field
                    :disabled="true"
                    :value="model.invoiceLinesSum | formatRub"
                    label="Сумма"
                />
            </v-col>
            <v-col cols="12" sm="auto">
                <cash-flows-modal
                    v-model="model"
                    :text="model.cashFlowsSum"
                    x-large
                />
            </v-col>
            <v-col cols="12" sm="auto">
                <v-text-field
                    :disabled="true"
                    :value="model.transferOutLinesSum | formatRub"
                    label="Отгружено"
                />
            </v-col>
            <v-col cols="12" sm="auto" v-if="!notCan">
                <v-text-field label="Примечание" v-model="model.PRIM" />
            </v-col>
            <v-col cols="12" sm="auto" v-if="!notCan">
                <v-text-field label="ИГК" v-model="model.IGK" />
            </v-col>
            <v-col cols="12" sm="auto">
                <invoice-status-select
                    :disabled="notCan"
                    v-model="model.STATUS"
                />
            </v-col>
            <v-col cols="12" sm="auto">
                <employee-select
                    v-model="model.STAFF_ID"
                    :can-empty="true"
                    :disabled="notCanEmployeeEdit"
                />
            </v-col>
            <v-col cols="12" sm="auto" v-if="!notCan">
                <v-btn
                    :block="!$vuetify.breakpoint.smAndUp"
                    :disabled="savePossible"
                    :fab="$vuetify.breakpoint.smAndUp"
                    :loading="loading"
                    @click="save"
                    class="mt-2"
                >
                    <v-icon v-if="$vuetify.breakpoint.smAndUp" color="green"
                        >mdi-content-save</v-icon
                    >
                    <span v-else>Сохранить</span>
                </v-btn>
                <v-btn
                    v-if="$vuetify.breakpoint.smAndUp"
                    fab
                    class="mt-2 ml-2"
                    @click="addInvoiceLine = true"
                    :disabled="notEditable || !model.SCODE || value.STATUS > 0"
                >
                    <v-icon color="primary">mdi-playlist-plus</v-icon>
                </v-btn>
                <v-btn
                    v-if="$vuetify.breakpoint.smAndUp"
                    fab
                    class="mt-2 ml-2"
                    @click="$emit('recalculateAll')"
                    :disabled="notEditable || notCan || value.STATUS > 0"
                    title="Пересчитать все строки из цены без НДС"
                >
                    <v-icon color="orange">mdi-calculator-variant</v-icon>
                </v-btn>
                <v-btn
                    v-if="$vuetify.breakpoint.smAndUp"
                    fab
                    class="mt-2 ml-2"
                    @click="transferOut"
                    :disabled="value.STATUS < 3 || value.STATUS > 4"
                    :loading="creatingTransferOut"
                >
                    <v-icon color="accent">mdi-clipboard-text-play</v-icon>
                </v-btn>
                <v-btn fab class="mt-2 ml-2" @click="setActiveInvoiceAndGoHome">
                    <v-icon color="primary">mdi-home</v-icon>
                </v-btn>
            </v-col>
            <v-col cols="12" sm="auto" v-if="$vuetify.breakpoint.smAndUp">
                <v-speed-dial :open-on-hover="true" direction="bottom">
                    <template v-slot:activator>
                        <v-btn :loading="downloading" class="mt-2" fab icon>
                            <v-icon>mdi-download</v-icon>
                        </v-btn>
                    </template>
                    <v-btn @click="pdfDialog = true" fab>
                        <v-icon color="red">mdi-adobe-acrobat</v-icon>
                    </v-btn>
                    <v-btn @click="download('xlsx')" fab>
                        <v-icon color="green">mdi-microsoft-excel</v-icon>
                    </v-btn>
                    <v-btn @click="receipt" fab v-if="!notCan">
                        <v-icon color="primary">mdi-paper-roll</v-icon>
                    </v-btn>
                    <v-btn @click="downloadUpd2Xml" fab :loading="upd2Loading" title="Скачать УПД-2 XML">
                        <v-icon color="orange">mdi-xml</v-icon>
                    </v-btn>
                    <v-btn @click="sendUpd2ToEdo" fab :loading="upd2Loading" title="Отправить УПД-2 в ЭДО">
                        <v-icon color="purple">mdi-send</v-icon>
                    </v-btn>
                    <v-btn @click="markUpd2Manual" fab :loading="upd2Loading" title="Я передал УПД-2 вручную">
                        <v-icon color="green">mdi-check-bold</v-icon>
                    </v-btn>
                    <v-btn @click="unmarkUpd2" fab :loading="upd2Loading" title="Откатить передачу УПД-2">
                        <v-icon color="red">mdi-undo</v-icon>
                    </v-btn>
                    <v-btn @click="$refs.mpUpdFile.click()" fab :loading="upd2Loading"
                           title="УПД маркетплейса: подставить номер, подписанта и коды ЧЗ">
                        <v-icon color="teal">mdi-storefront</v-icon>
                    </v-btn>
                </v-speed-dial>
                <input type="file" ref="mpUpdFile" accept=".xml" style="display: none" @change="patchMpUpd" />
                <invoice-pdf-menu
                    v-model="value"
                    :pdf-dialog="pdfDialog"
                    @close="pdfDialog = false"
                    @downloading="setDownloading"
                    :sort-by="sortBy"
                    :sort-desc="sortDesc"
                />
            </v-col>
        </v-row>
        <v-dialog v-model="addInvoiceLine">
            <invoice-line-add
                :invoice="model"
                @close="addInvoiceLine = false"
                @closeWithReload="closeWithReload"
            />
        </v-dialog>
    </v-form>
</template>

<script>
import moment from 'moment';
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
import { mapGetters } from "vuex";
import CashFlowsModal from "../CashFlowsModal.vue";

export default {
    name: "InvoiceEdit",
    components: {
        CashFlowsModal,
        EmployeeSelect,
        DatePicker,
        FirmHistorySelect,
        InvoiceLineAdd,
        InvoicePdfMenu,
        InvoicePdf,
        FirmSelect,
        InvoiceStatusSelect,
        BuyerSelect,
    },
    mixins: [editMixin, utilsMixin],
    props: {
        sortBy: { type: Array, default: () => [] },
        sortDesc: { type: Array, default: () => [] },
    },
    data() {
        return {
            MODEL: "INVOICE",
            downloading: false,
            pdfDialog: false,
            addInvoiceLine: false,
            firmId: undefined,
            creatingTransferOut: false,
            upd2Loading: false,
        };
    },
    computed: {
        ...mapGetters({ isAdmin: "AUTH/IS_ADMIN" }),
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
            return !this.$store.getters["AUTH/HAS_PERMISSION"](
                "invoice.update"
            );
        },
        notCanEmployeeEdit() {
            return !this.$store.getters["AUTH/HAS_PERMISSION"](
                "invoice.employee"
            );
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
            },
        },
    },
    methods: {
        async transferOut() {
            try {
                this.creatingTransferOut = true;
                const transferOut = await this.$store.dispatch(
                    "TRANSFER-OUT/CREATE",
                    {
                        item: { SCODE: this.value.SCODE },
                        options: {
                            with: ["buyer", "employee", "firm", "invoice"],
                            aggregateAttributes: [
                                "transferOutLinesCount",
                                "transferOutLinesSum",
                            ],
                        },
                    }
                );
                this.$router.push({
                    name: "transfer-out",
                    params: { id: transferOut.data.SFCODE },
                });
            } catch (e) {}
            this.creatingTransferOut = false;
        },
        async receipt() {
            this.downloading = true;
            await this.$store.dispatch("INVOICE/RECEIPT", this.value.SCODE);
            this.downloading = false;
        },
        download(type) {
            this.downloading = true;
            this.pdfDialog = false;
            const { withVAT, withStamp, newAccount } = this;
            const date = moment(this.value.DATA).format('DD.MM.YYYY');
            const download =
                type === "pdf"
                    ? this.$store.dispatch("INVOICE/PDF", {
                          id: this.value.SCODE,
                          query: { withVAT, withStamp, newAccount },
                      })
                    : this.$store.dispatch("INVOICE-LINE/SAVE", {
                          filename: `Счет № ${this.value.NS} от ${date}.xlsx`,
                          with: ["category", "good", "name"],
                          filterAttributes: ["invoice.SCODE"],
                          filterOperators: ["="],
                          filterValues: [this.value.SCODE],
                          sortBy: ["category.CATEGORY", "name.NAME"],
                          sortDesc: [false, false],
                      });
            download
                .then(() => {})
                .catch(() => {})
                .then(() => (this.downloading = false));
        },
        setDownloading(downloading) {
            this.downloading = downloading;
        },
        closeWithReload(id) {
            this.addInvoiceLine = false;
            this.$emit("newInvoiceLine", id);
            this.$emit("reloadInvoice");
        },
        setActiveInvoiceAndGoHome() {
            if (!this.value || !this.value.SCODE) {
                return;
            }
            this.$store.commit("INVOICE/SET-CURRENT", this.value.SCODE);
            this.$router.push({ name: "home" });
        },
        async downloadUpd2Xml() {
            this.upd2Loading = true;
            try {
                const response = await window.axios.get(
                    `/api/invoice/upd2-xml/${this.value.SCODE}`,
                    { responseType: "blob" }
                );
                const cd = response.headers["content-disposition"] || "";
                const match = cd.match(/filename="?([^";]+)"?/);
                const filename = match ? match[1] : `upd2_${this.value.SCODE}.xml`;
                const url = URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement("a");
                link.href = url;
                link.download = filename;
                link.click();
                URL.revokeObjectURL(url);
            } catch (e) {
                this.$store.commit("SNACKBAR/ERROR", "Ошибка скачивания УПД-2 XML");
            } finally {
                this.upd2Loading = false;
            }
        },
        async patchMpUpd(e) {
            const file = e.target.files[0];
            e.target.value = "";
            if (!file) return;
            this.upd2Loading = true;
            try {
                const form = new FormData();
                form.append("file", file);
                const { data } = await window.axios.post(
                    `/api/invoice/mp-upd-xml/${this.value.SCODE}`,
                    form,
                    { headers: { "Content-Type": "multipart/form-data" } }
                );
                // xml приходит base64-ом: файл в windows-1251, через JSON-строку его не протащить
                const bytes = Uint8Array.from(atob(data.xml), (c) => c.charCodeAt(0));
                const url = URL.createObjectURL(new Blob([bytes], { type: "application/xml" }));
                const link = document.createElement("a");
                link.href = url;
                link.download = data.filename;
                link.click();
                URL.revokeObjectURL(url);
                (data.warnings || []).forEach((w) =>
                    this.$store.commit("SNACKBAR/PUSH", {
                        text: w,
                        color: "warning",
                        status: true,
                        timeout: 15000,
                    })
                );
            } catch (err) {
                const msg = err.response?.data?.message || "Ошибка обработки УПД маркетплейса";
                this.$store.commit("SNACKBAR/ERROR", msg);
            } finally {
                this.upd2Loading = false;
            }
        },
        async sendUpd2ToEdo() {
            if (!confirm("Отправить УПД-2 в ЭДО? Коды будут помечены как переданные.")) return;
            this.upd2Loading = true;
            try {
                const { data } = await window.axios.post("/api/sbis/export", {
                    type: "upd2",
                    invoice_id: this.value.SCODE,
                });
                this.$store.commit("SNACKBAR/PUSH", {
                    text: `УПД-2 отправлен. Message ID: ${data.message_id}`,
                    color: "success",
                    status: true,
                    timeout: 10000,
                });
            } catch (e) {
                const msg = e.response?.data?.message || "Ошибка отправки УПД-2";
                this.$store.commit("SNACKBAR/ERROR", msg);
            } finally {
                this.upd2Loading = false;
            }
        },
        async markUpd2Manual() {
            if (!confirm("Пометить коды как переданные вручную? Используй если XML отдан в ЭДО/ЛК Озон вне системы.")) return;
            this.upd2Loading = true;
            try {
                const { data } = await window.axios.post(
                    "/api/mark-codes/mark-as-transferred",
                    { invoice_id: this.value.SCODE, transfer_type: 2, retire_reason: 1 }
                );
                this.$store.commit("SNACKBAR/PUSH", {
                    text: `Помечено ${data.count} кодов как переданные`,
                    color: "success",
                    status: true,
                    timeout: 10000,
                });
            } catch (e) {
                const msg = e.response?.data?.message || "Ошибка ручной пометки УПД-2";
                this.$store.commit("SNACKBAR/ERROR", msg);
            } finally {
                this.upd2Loading = false;
            }
        },
        async unmarkUpd2() {
            if (!confirm("Откатить передачу кодов УПД-2? Коды вернутся в оборот.")) return;
            this.upd2Loading = true;
            try {
                const { data } = await window.axios.post(
                    "/api/mark-codes/unmark-as-transferred",
                    { invoice_id: this.value.SCODE }
                );
                this.$store.commit("SNACKBAR/PUSH", {
                    text: `Откачено ${data.count} кодов`,
                    color: "success",
                    status: true,
                    timeout: 10000,
                });
            } catch (e) {
                const msg = e.response?.data?.message || "Ошибка отката УПД-2";
                this.$store.commit("SNACKBAR/ERROR", msg);
            } finally {
                this.upd2Loading = false;
            }
        },
    },
};
</script>

<style scoped></style>
