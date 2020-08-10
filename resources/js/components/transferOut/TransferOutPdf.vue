<template>
    <div>
        <v-btn @click="pdfDialog=true" icon :loading="downloading">
            <v-icon color="red">mdi-adobe-acrobat</v-icon>
        </v-btn>
        <transfer-out-pdf-menu v-model="value"
                               :pdf-dialog="pdfDialog"
                               @close="pdfDialog=false"
                               @downloading="setDownloading"
        />
    </div>
</template>

<script>
import TransferOutPdfMenu from "./TransferOutPdfMenu";
export default {
    name: "TransferOutPdf",
    components: {TransferOutPdfMenu},
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
    },
}
</script>

<style scoped>

</style>
