<template>
    <v-card class="m-1">
        <v-card-title class="text-center">
            <v-edit-dialog  ref="dialog">
                <v-container v-if="currentInvoice">
                    <v-row>
                        <v-col>
                            № {{ invoice.NS }} от {{ invoice.DATA | formatDate }}
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col>
                            {{ invoice.buyer.SHORTNAME }}
                        </v-col>
                    </v-row>
                    <v-row>
                        {{ invoice.invoiceLinesCount }} строк на {{ invoice.invoiceLinesSum | formatRub }}
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
                    <invoice-select v-model="currentInvoice" />
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
    }
}
</script>

<style scoped>

</style>
