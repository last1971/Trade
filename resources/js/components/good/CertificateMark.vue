<template>
    <!-- Наведение — сертификаты товара; клик по строке — открыть файл, один сертификат — клик по значку -->
    <v-menu v-if="certified !== undefined" open-on-hover offset-y :close-on-content-click="false" @input="load">
        <template v-slot:activator="{ on, attrs }">
            <v-icon small class="ml-1" :color="certified ? 'success' : 'error'"
                    v-bind="attrs" v-on="on" @click.stop="openSingle"
            >
                mdi-certificate-outline
            </v-icon>
        </template>
        <v-list dense>
            <v-list-item v-if="!certificates">
                <v-list-item-content class="caption">загрузка…</v-list-item-content>
            </v-list-item>
            <v-list-item v-for="certificate in certificates" :key="certificate.id" @click="open(certificate)">
                <v-list-item-content>
                    <v-list-item-title :class="{'error--text': certificate.is_expired}">
                        {{ certificate.number }}
                    </v-list-item-title>
                    <v-list-item-subtitle>
                        {{ term(certificate) }}
                        <span v-if="certificate.marketplaces.length">
                            — {{ certificate.marketplaces.map((marketplace) => marketplace.name).join(', ') }}
                        </span>
                    </v-list-item-subtitle>
                </v-list-item-content>
            </v-list-item>
        </v-list>
    </v-menu>
</template>

<script>
// Значок «у товара есть сертификат» рядом со значком ЧЗ: зелёный — есть действующий,
// красный — только просроченные, нет значка — сертификата нет. Устроен как ChzMark:
// какие товары с сертификатами — из стора CERTIFICATE (раз за сессию), здесь только отображение.
export default {
    name: "CertificateMark",
    props: {
        code: {
            type: [Number, String],
            required: true,
        },
    },
    data() {
        return {
            // Сертификаты товара грузятся при первом наведении.
            certificates: null,
        };
    },
    created() {
        this.$store.dispatch('CERTIFICATE/FETCH_GOODS');
    },
    computed: {
        certified() {
            return this.$store.getters['CERTIFICATE/GOOD_CERTIFIED'](this.code);
        },
    },
    methods: {
        load(opened) {
            if (!opened || this.certificates) return Promise.resolve(this.certificates);
            return this.$store.dispatch('CERTIFICATE/GOOD_CERTIFICATES', this.code)
                .catch(() => [])
                .then((certificates) => this.certificates = certificates);
        },
        openSingle() {
            this.load(true).then((certificates) => {
                if (certificates && certificates.length === 1) this.open(certificates[0]);
            });
        },
        open(certificate) {
            this.$store.dispatch('CERTIFICATE/OPEN', certificate);
        },
        term(certificate) {
            if (!certificate.date_to) return 'бессрочно';
            const date = this.$options.filters.formatDate(certificate.date_to);
            return certificate.is_expired ? 'просрочен (до ' + date + ')' : 'до ' + date;
        },
    },
}
</script>
