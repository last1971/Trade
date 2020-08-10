<template>
    <div>
        <v-btn :loading="downloading" @click="pdfDialog=true" icon>
            <v-icon color="red">mdi-adobe-acrobat</v-icon>
        </v-btn>
        <invoice-pdf-menu
            v-model="value"
            :pdf-dialog="pdfDialog"
            @close="pdfDialog=false"
            @downloading="setDownloading"
        />
    </div>
</template>

<script>
    import InvoicePdfMenu from "./InvoicePdfMenu";
    export default {
        name: "InvoicePdf",
        components: {InvoicePdfMenu},
        props: ['value'],
        data() {
            return {
                pdfDialog: false,
                downloading: false,
            }
        },
        methods: {
            setDownloading(downloading) {
                this.downloading  = downloading
            }
        }
    }
</script>

<style scoped>

</style>
