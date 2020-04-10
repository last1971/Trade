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
                            :disabled="notEditable"
                            :value="invoice.DATA | formatDate"
                            label="Дата"
                            prepend-icon="mdi-calendar-edit"
                            readonly
                            v-on="on"
                        />
                    </template>
                    <v-date-picker @input="datePicker = false" v-model="invoice.DATA"></v-date-picker>
                </v-menu>
            </v-col>
            <v-col cols="12" sm="auto">
                <v-text-field :disabled="notEditable"
                              :rules="[rules.required, rules.isInteger]"
                              label="Номер"
                              v-model="invoice.NS"
                />
            </v-col>
            <v-col cols="12" sm="auto">
                <buyer-select :disabled="notEditable" v-model="invoice.POKUPATCODE"/>
            </v-col>
            <v-col cols="12" sm="auto">
                <firm-select :disabled="notEditable" v-model="invoice.FIRM_ID"/>
            </v-col>
            <v-col cols="12" sm="auto">
                <invoice-status-select v-model="invoice.STATUS"/>
            </v-col>
            <v-col cols="12" sm="auto">
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
        </v-row>
        <v-row>
            <v-col cols="12" sm="8">
                <v-text-field label="Примечание" v-model="invoice.PRIM"/>
            </v-col>
        </v-row>
    </v-form>
</template>

<script>
    import BuyerSelect from "./BuyerSelect";
    import utilsMixin from "../mixins/utilsMixin";
    import InvoiceStatusSelect from "./InvoiceStatusSelect";
    import FirmSelect from "./FirmSelect";

    export default {
        name: "InvoiceEdit",
        components: {FirmSelect, InvoiceStatusSelect, BuyerSelect},
        mixins: [utilsMixin],
        props: {
            value: {
                type: Object,
                required: true,
            }
        },
        data() {
            return {
                invoice: {},
                datePicker: false,
                loading: false,
            }
        },
        computed: {
            notEditable() {
                return this.invoice.transferOutLinesSum > 0;
            },
            savePossible() {
                const a = _.pick(_.omit(this.value, ['DATA']), this.fillable);
                const b = _.pick(_.omit(this.invoice, ['DATA']), this.fillable);
                const d = this.value.DATA ? this.value.DATA.substr(0, 10) : undefined;
                return _.isEqual(a, b) && this.invoice.DATA === d;
            },
            options() {
                return this.$store.getters['USER/LOCAL_OPTION']('INVOICE');
            },
            fillable() {
                return this.$store.getters['INVOICE/FILLABLE'];
            }
        },
        created() {
            this.initialInvoice();
        },
        watch: {
            value(v) {
                this.initialInvoice();
            }
        },
        methods: {
            initialInvoice() {
                this.invoice = _.cloneDeep(this.value);
                this.invoice.DATA = this.invoice.DATA.substr(0, 10);
            },
            save() {
                this.loading = true;
                this.$store.dispatch(
                    'INVOICE/UPDATE',
                    {item: this.invoice, options: this.options}
                )
                    .then(() => {
                    })
                    .catch(() => {
                    })
                    .then(() => {
                        this.loading = false;
                        this.initialInvoice();
                    });
            }
        }
    }
</script>

<style scoped>

</style>
