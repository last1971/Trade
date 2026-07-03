<template>
    <v-dialog v-model="adding" max-width="800">
        <template v-slot:activator="{ on }">
            <v-btn rounded color="success" v-on="on">
                <v-icon left>mdi-plus</v-icon>
                Добавить сертификаты
            </v-btn>
        </template>
        <v-card>
            <v-card-title class="headline">
                <span class="headline">Загрузить сертификаты</span>
                <v-spacer/>
                <v-btn @click="close" icon right>
                    <v-icon color="red">
                        mdi-close
                    </v-icon>
                </v-btn>
            </v-card-title>
            <v-divider/>
            <v-card-text>
                <file-drop
                    @filesSelected="filesSelected"
                    accept="application/pdf,image/jpeg,image/png"
                    multiple
                    text="Перетащите файлы сертификатов (PDF/JPG/PNG) или кликните"
                    class="mb-4 py-4"
                />
                <v-simple-table v-if="files.length">
                    <template v-slot:default>
                        <thead>
                        <tr>
                            <th>Файл</th>
                            <th>Номер сертификата</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(file, index) in files" :key="index">
                            <td>{{ file.file.name }}</td>
                            <td>
                                <v-text-field v-model="file.number" dense :rules="[rules.required]"/>
                            </td>
                        </tr>
                        </tbody>
                    </template>
                </v-simple-table>
                <v-combobox
                    v-if="files.length"
                    v-model="type"
                    :items="types"
                    :rules="[rules.required]"
                    label="Тип документа"
                />
                <v-row v-if="files.length" dense>
                    <v-col cols="6">
                        <date-picker label="Действует с" v-model="date_from"/>
                    </v-col>
                    <v-col cols="6">
                        <date-picker label="Действует по" v-model="date_to"/>
                    </v-col>
                </v-row>
                <v-text-field v-if="files.length" label="Название" v-model="name"/>
                <v-text-field v-if="files.length" label="Примечание" v-model="remark"/>
            </v-card-text>
            <v-card-actions class="d-flex justify-end">
                <v-btn rounded color="success" :disabled="saveNotPossible" :loading="uploading" @click="save">
                    <v-icon left>
                        mdi-content-save
                    </v-icon>
                    Загрузить
                </v-btn>
                <v-btn rounded color="error" @click="close">
                    <v-icon left>
                        mdi-close
                    </v-icon>
                    Отменить
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
import utilsMixin from "../../mixins/utilsMixin";
import FileDrop from "../FileDrop.vue";
import DatePicker from "../DatePicker.vue";

export default {
    name: "CertificateAdd",
    components: {FileDrop, DatePicker},
    mixins: [utilsMixin],
    data() {
        return {
            adding: false,
            uploading: false,
            files: [],
            type: null,
            types: [
                'Сертификат соответствия',
                'Декларация соответствия',
                'Отказное письмо',
                'Свидетельство о гос. регистрации',
            ],
            name: null,
            remark: null,
            date_from: null,
            date_to: null,
        }
    },
    computed: {
        saveNotPossible() {
            return !this.files.length
                || this.rules.required(this.type) !== true
                || this.files.some((file) => this.rules.required(file.number) !== true);
        }
    },
    created() {
        this.$store.dispatch('CERTIFICATE/TYPES')
            .then((types) => {
                this.types = _.union(this.types, types);
            });
    },
    methods: {
        filesSelected(fileList) {
            const files = fileList.files || fileList;
            Array.from(files).forEach((file) => {
                this.files.push({
                    file,
                    number: file.name.replace(/\.[^.]+$/, ''),
                });
            });
        },
        close() {
            this.adding = false;
            this.files = [];
            this.type = null;
            this.name = null;
            this.remark = null;
            this.date_from = null;
            this.date_to = null;
        },
        async save() {
            this.uploading = true;
            try {
                for (const file of this.files) {
                    await this.$store.dispatch('CERTIFICATE/UPLOAD', {
                        file: file.file,
                        number: file.number,
                        type: this.type,
                        name: this.name,
                        remark: this.remark,
                        date_from: this.date_from,
                        date_to: this.date_to,
                    });
                }
                this.$emit('reload');
                this.close();
            } catch (e) {
                console.error(e);
            } finally {
                this.uploading = false;
            }
        }
    }
}
</script>

<style scoped>

</style>
