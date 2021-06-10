<template>
    <v-card class="m-1">
        <v-card-title class="text-center">
            <v-edit-dialog ref="dialog">
                <v-container v-if="currentInvoice">
                    <v-row class="text-caption d-flex justify-center">
                        Счет № {{ invoice.NS }} от {{ invoice.DATA | formatDate }}
                    </v-row>
                    <v-row class="text-subtitle-2 d-flex justify-center">
                        Для {{ invoice.buyer.SHORTNAME }}
                    </v-row>
                    <v-row class="text-subtitle-2 d-flex justify-center">
                        {{ invoice.invoiceLinesCount }} строк на&nbsp;<b> {{ invoice.invoiceLinesSum | formatRub }} </b>
                    </v-row>
                </v-container>
                <v-container v-else>
                    <v-row>
                        <v-col>
                            В Ы Б Е Р И С Ч Е Т
                        </v-col>
                    </v-row>
                </v-container>
                <template v-slot:input>
                    <invoice-select v-model="currentInvoice" @input="select"/>
                </template>
            </v-edit-dialog>
        </v-card-title>
    </v-card>
</template>

<script>
import InvoiceSelect from "./InvoiceSelect";
export default {
    name: "InvoiceCard",
    components: {InvoiceSelect},
    computed: {
        currentInvoice: {
            get() {
                return this.$store.getters['INVOICE/GET-CURRENT'];
            },
            set(currentInvoice) {
                this.$store.commit('INVOICE/SET-CURRENT', currentInvoice);
            }
        },
        invoice() {
            return this.$store.getters['INVOICE/GET'](this.currentInvoice);
        }
    },
    methods: {
        select() {
            this.$refs.dialog.isActive = false;
        }
    }
}
</script>

<style scoped>

</style>
