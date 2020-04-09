<template>
    <v-form class="mx-2">
        <v-row>
            <v-col>
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
                            :disabled="invoiceNotEditable"
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
            <v-col>
                <v-text-field :disabled="invoiceNotEditable" label="Номер" v-model="invoice.NS"/>
            </v-col>
        </v-row>
    </v-form>
</template>

<script>
    export default {
        name: "InvoiceEdit",
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
                invoiceNotEditable: false,
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
            }
        }
    }
</script>

<style scoped>

</style>
