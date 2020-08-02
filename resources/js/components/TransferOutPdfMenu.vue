<template>
    <v-dialog max-width="400px" persistent v-model="pdfDialog">
        <v-card>
            <v-card-title>
                <span class="headline">Параметры счет-фактуры</span>
                <v-spacer/>
                <v-btn @click="$emit('close')" icon right>
                    <v-icon color="red">
                        mdi-close
                    </v-icon>
                </v-btn>
            </v-card-title>
            <v-divider></v-divider>
            <v-card-text>
                <v-container>
                    <v-row>
                        <v-switch class="ml-4"
                                  inset
                                  label="Корпус"
                                  v-model="body"
                        />
                        <v-switch class="ml-4"
                                  inset
                                  label="Производитель"
                                  v-model="producer"
                        />
                        <v-switch class="ml-4"
                                  inset
                                  label="Категория"
                                  v-model="category"
                        />
                        <v-switch :label="'Разделитель ' + (divider ? '/' : 'пробел')"
                                  inset
                                  v-model="divider"
                                  class="ml-4"
                        />
                        <v-btn @click="download()" class="ml-4 " fab>
                            <v-icon dark>mdi-download</v-icon>
                        </v-btn>
                    </v-row>
                </v-container>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script>
export default {
    name: "TransferOutPdfMenu",
    props: {
        value: { type: Object },
        pdfDialog: { type: Boolean, default: false },
    },
    data() {
        return {
            body: true,
            producer: true,
            category: true,
            divider: true,
        }
    },
    watch: {
        downloading() {
            this.$emit('downloading', this.downloading);
        },
    },
    methods: {
        download() {
            this.$emit('downloading', true);
            this.$emit('close');
            const {body, producer, category, divider} = this;
            const download = this.$store.dispatch(
                'TRANSFER-OUT/PDF',
                { id: this.value.SFCODE, query: {body, producer, category, divider} }
            );
            download
                .then(() => {
                })
                .catch(() => {
                })
                .then(() => {
                    this.$emit('downloading', false);
                });
        }
    }
}
</script>

<style scoped>

</style>
