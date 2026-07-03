<template>
    <v-card v-if="certificates.length" outlined class="my-4">
        <v-card-title class="subtitle-2 py-2">Сертификаты</v-card-title>
        <v-divider/>
        <v-simple-table dense>
                <template v-slot:default>
                    <thead>
                    <tr>
                        <th>Номер</th>
                        <th>Тип</th>
                        <th>Название</th>
                        <th>Действует с</th>
                        <th>Действует по</th>
                        <th>Площадки</th>
                        <th>Файл</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="certificate in certificates"
                        :key="certificate.id"
                        :class="certificate.is_expired ? 'certificate-expired' : ''"
                    >
                        <td>{{ certificate.number }}</td>
                        <td>{{ certificate.type }}</td>
                        <td>{{ certificate.name }}</td>
                        <td>{{ certificate.date_from ? $options.filters.formatDate(certificate.date_from) : '—' }}</td>
                        <td>
                            {{ certificate.date_to ? $options.filters.formatDate(certificate.date_to) : '—' }}
                            <v-icon v-if="certificate.is_expired" color="error" small title="Срок действия истёк!">
                                mdi-alert
                            </v-icon>
                        </td>
                        <td>
                            <v-chip
                                v-for="marketplace in certificate.marketplaces"
                                :key="marketplace.id"
                                :color="certificate.is_expired ? 'error' : 'success'"
                                :title="certificate.is_expired ? 'Сертификат просрочен — заменить на площадке!' : ''"
                                class="ma-1"
                                small
                                outlined
                            >
                                {{ marketplace.name }}
                            </v-chip>
                        </td>
                        <td>
                            <v-btn icon small @click="open(certificate)" title="Открыть файл">
                                <v-icon small>mdi-eye</v-icon>
                            </v-btn>
                            <v-btn icon small @click="download(certificate)" title="Скачать файл">
                                <v-icon small>mdi-download</v-icon>
                            </v-btn>
                        </td>
                    </tr>
                    </tbody>
                </template>
            </v-simple-table>
    </v-card>
</template>

<script>
export default {
    name: "GoodCertificates",
    props: {
        value: {type: [Number, String], required: true},
    },
    data() {
        return {
            certificates: [],
        }
    },
    watch: {
        value: {
            immediate: true,
            handler() {
                this.load();
            }
        }
    },
    methods: {
        load() {
            if (!this.value) {
                this.certificates = [];
                return;
            }
            axios.get('/api/good/' + this.value + '/certificates')
                .then((response) => {
                    this.certificates = response.data;
                })
                .catch(() => {
                    this.certificates = [];
                });
        },
        open(certificate) {
            this.$store.dispatch('CERTIFICATE/OPEN', certificate);
        },
        download(certificate) {
            this.$store.dispatch('CERTIFICATE/DOWNLOAD', certificate);
        },
    }
}
</script>

<style>
.certificate-expired td {
    background-color: rgba(255, 72, 66, 0.16);
}
</style>
