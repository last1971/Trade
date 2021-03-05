<template>
    <v-form class="mx-2">
        <v-row>
            <v-col cols="12" sm="auto">
                <v-text-field :disabled="true"
                              label="Наш номер"
                              v-model="model.NZAKAZ"
                />
            </v-col>
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
                            :disabled="notEditable"
                            :value="model.INVOICE_DATA | formatDate"
                            label="Дата"
                            prepend-icon="mdi-calendar-edit"
                            readonly
                            v-on="on"
                        />
                    </template>
                    <v-date-picker @input="datePicker = false" first-day-of-week="1" v-model="model.INVOICE_DATA"/>
                </v-menu>
            </v-col>
            <v-col cols="12" sm="auto">
                <v-text-field :disabled="notEditable"
                              :rules="[rules.required]"
                              label="Номер"
                              v-model="model.INVOICE_NUM"
                />
            </v-col>
            <v-col cols="12" sm="auto">
                <seller-select :disabled="notEditable" v-model="model.WHEREISPOSTCODE"/>
            </v-col>
            <v-col cols="12" sm="auto">
                <v-text-field label="Сумма" :value="(model.orderLinesSum || 0) | formatRub" disabled/>
            </v-col>
            <v-col cols="12" sm="auto">
                <v-menu
                    :close-on-content-click="false"
                    :nudge-right="40"
                    min-width="290px"
                    offset-y
                    transition="scale-transition"
                    v-model="datePickerCome"
                >
                    <template v-slot:activator="{ on }">
                        <v-text-field
                            :disabled="notEditable"
                            :value="model.DATA_PRIH | formatDate"
                            label="Приходит"
                            prepend-icon="mdi-calendar-edit"
                            readonly
                            v-on="on"
                        />
                    </template>
                    <v-date-picker @input="datePickerCome = false" first-day-of-week="1" v-model="model.DATA_PRIH"/>
                </v-menu>
            </v-col>
            <v-col cols="12" sm="auto">
                <v-text-field label="Примечание" v-model="model.PRIM"/>
            </v-col>
            <v-col cols="12" sm="auto">
                <order-status-select v-model="model.STATUS"/>
            </v-col>
            <v-col cols="12" sm="auto">
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
                       @click="addOrderLine = true"
                       :disabled="notEditable || !model.ID"
                >
                    <v-icon color="primary">mdi-playlist-plus</v-icon>
                </v-btn>
            </v-col>
        </v-row>
        <v-row>
            <v-col>
                <v-file-input
                    :disabled="model.STATUS !== 0 || loading"
                    @change="upload"
                    accept=".csv,.xlsx"
                    label="Импорт заказа"
                    v-model="importFiles"
                    :loading="loading"
                />
            </v-col>
        </v-row>
        <v-dialog v-model="addOrderLine">
            <order-line-add :order="model"
                            @close="addOrderLine=false"
                            @closeWithReload="closeWithReload"
            />
        </v-dialog>
    </v-form>
</template>

<script>
    import SellerSelect from "../SellerSelect";
    import editMixin from "../../mixins/editMixin";
    import utilsMixin from "../../mixins/utilsMixin";
    import OrderStatusSelect from "./OrderStatusSelect";
    import moment from "moment";
    import FileDrop from "../FileDrop";
    import OrderLineAdd from "./OrderLineAdd";

    export default {
        name: "OrderEdit",
        components: {OrderLineAdd, FileDrop, OrderStatusSelect, SellerSelect},
        mixins: [editMixin, utilsMixin],
        data() {
            return {
                MODEL: 'ORDER',
                datePickerCome: false,
                importFiles: null,
                loading: false,
                addOrderLine: false,
            }
        },
        computed: {
            notEditable() {
                return this.model.INSUM > 0;
            },
            savePossible() {
                const a = _.pick(_.omit(this.value, ['INVOICE_DATA', 'DATA_PRIH']), this.fillable);
                const b = _.pick(_.omit(this.model, ['INVOICE_DATA', 'DATA_PRIH']), this.fillable);
                const d1 = this.value.INVOICE_DATA ? this.value.INVOICE_DATA.substr(0, 10) : undefined
                const d2 = this.value.DATA_PRIH ? this.value.DATA_PRIH.substr(0, 10) : undefined;
                return _.isEqual(a, b) && this.model.INVOICE_DATA === d1 && this.model.DATA_PRIH === d2;
            },
            showImportOrderLines() {
                return this.importOrderLines.length;
            }
        },
        methods: {
            initialModel() {
                this.model = _.cloneDeep(this.value);
                this.model.INVOICE_DATA = this.model.INVOICE_DATA
                    ? this.model.INVOICE_DATA.substr(0, 10)
                    : moment().format('Y-MM-DD');
                this.model.DATA_PRIH = this.model.DATA_PRIH
                    ? this.model.DATA_PRIH.substr(0, 10)
                    : moment().format('Y-MM-DD');
            },
            upload(files) {
                this.loading = true;
                this.$store.dispatch('ORDER-IMPORT-LINE/UPLOAD', {files})
                    .then((response) => {
                        this.$emit('import', response.data);
                    })
                    .catch(() => {
                    })
                    .then(() => this.loading = false)
            },
            async closeWithReload(id) {
                this.addOrderLine = false;
                this.$emit('newOrderLine', id);
                this.$emit('reloadOrder');
            }
        }
    }
</script>

<style scoped>

</style>
