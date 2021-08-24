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
                    <v-date-picker @input="datePicker = false" v-model="model.DATA"></v-date-picker>
                </v-menu>
            </v-col>
            <v-col cols="12" sm="auto">
                <v-text-field :disabled="notEditable || notCan"
                              :rules="[rules.required, rules.isInteger]"
                              label="Номер"
                              v-model="model.NSF"
                />
            </v-col>
            <v-col cols="12" sm="auto">
                <buyer-select :disabled="notEditable || notCan" v-model="model.POKUPATCODE"/>
            </v-col>
            <v-col cols="12" sm="auto">
                <firm-select :disabled="notEditable || notCan" v-model="model.FIRM_ID"/>
            </v-col>
            <v-col cols="12" sm="auto" v-if="!notCan">
                <v-text-field label="Примечание" v-model="model.PRIM"/>
            </v-col>
            <v-col cols="12" sm="auto">
                <v-text-field label="Сумма" :value="model.transferOutLinesSum | formatRub" disabled/>
            </v-col>
            <v-col class="d-flex justify-center align-center" cols="12" sm="auto">
                <router-link :to="{ name: 'invoice', params: { id: model.SCODE } }"
                             class="title"
                >
                    Счет № {{ model.invoice.NS }} от {{ model.invoice.DATA | formatDate }}
                </router-link>
            </v-col>
            <v-col cols="12" sm="auto" v-if="!notCan">
                <v-btn :block="!$vuetify.breakpoint.smAndUp"
                       :disabled="savePossible"
                       :fab="$vuetify.breakpoint.smAndUp"
                       :loading="loading"
                       @click="save"
                       class="mt-2"
                >
                    <v-icon v-if="$vuetify.breakpoint.smAndUp" color="success">mdi-content-save</v-icon>
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
                    <v-btn @click="pdfDialog=true" fab>
                        <v-icon color="red">mdi-adobe-acrobat</v-icon>
                    </v-btn>
                    <v-btn @click="download('xml')" fab>
                        <v-icon color="blue">mdi-xml</v-icon>
                    </v-btn>
                    <v-btn @click="download('xlsx')" fab>
                        <v-icon color="green">mdi-microsoft-excel</v-icon>
                    </v-btn>
                </v-speed-dial>
                <transfer-out-pdf-menu v-model="model"
                                       :pdf-dialog="pdfDialog"
                                       @close="pdfDialog=false"
                                       @downloading="setDownloading"
                />
            </v-col>
        </v-row>
    </v-form>
</template>

<script>
    import utilsMixin from "../../mixins/utilsMixin";
    import BuyerSelect from "../BuyerSelect";
    import FirmSelect from "../FirmSelect";
    import editMixin from "../../mixins/editMixin";
    import TransferOutPdfMenu from "./TransferOutPdfMenu";

    export default {
        name: "TransferOutEdit",
        mixins: [editMixin, utilsMixin],
        components: {TransferOutPdfMenu, BuyerSelect, FirmSelect},
        data() {
            return {
                MODEL: 'TRANSFER-OUT',
                downloading: false,
                pdfDialog: false,
            }
        },
        computed: {
            savePossible() {
                const a = _.pick(_.omit(this.value, ['DATA']), this.fillable);
                const b = _.pick(_.omit(this.model, ['DATA']), this.fillable);
                const d = this.value.DATA ? this.value.DATA.substr(0, 10) : undefined;
                return _.isEqual(a, b) && this.model.DATA === d;
            },
            notCan() {
                return !this.$store.getters['AUTH/HAS_PERMISSION']('transfer-out.update');
            }
        },
        methods: {
            download(type) {
                this.downloading = true;
                let download;
                switch (type) {
                    case 'pdf':
                        download = this.$store.dispatch('TRANSFER-OUT/PDF', this.value.SFCODE);
                        break;
                    case 'xlsx':
                        download = this.$store.dispatch('TRANSFER-OUT-LINE/SAVE', {
                            with: ['category', 'good', 'name', 'transferOut.buyer'],
                            filterAttributes: [
                                'SFCODE',
                            ],
                            filterOperators: ['='],
                            filterValues: [this.value.SFCODE],
                            sortBy: ['category.CATEGORY', 'name.NAME'],
                            sortDesc: [false, false],
                        });
                        break;
                    default:
                        download = this.$store.dispatch('TRANSFER-OUT/XML', this.value.SFCODE)
                }
                download
                    .then(() => {
                    })
                    .catch(() => {
                    })
                    .then(() => this.downloading = false);
            },
            setDownloading(downloading) {
                this.downloading = downloading
            }
        },
    }
</script>

<style scoped>

</style>
