<template>
    <v-row dense>
        <v-col cols="12" md="7">
            <div class="subtitle-2 mt-2">Товары сертификата</div>
            <v-list dense>
                <v-list-item v-for="certificateGood in value.certificateGoods" :key="certificateGood.id">
                    <v-list-item-content>
                        <router-link v-if="certificateGood.good"
                                     :to="{name: 'good', params: {id: certificateGood.good_id}}"
                        >
                            {{ certificateGood.good.name ? certificateGood.good.name.NAME : certificateGood.good_id }}
                            <span v-if="certificateGood.good.BODY">/ {{ certificateGood.good.BODY }}</span>
                            <span v-if="certificateGood.good.PRODUCER">/ {{ certificateGood.good.PRODUCER }}</span>
                        </router-link>
                        <span v-else>Товар {{ certificateGood.good_id }}</span>
                    </v-list-item-content>
                    <v-list-item-action>
                        <v-btn icon small @click="detach(certificateGood)" title="Отвязать товар">
                            <v-icon color="red" small>mdi-link-off</v-icon>
                        </v-btn>
                    </v-list-item-action>
                </v-list-item>
            </v-list>
            <v-row dense align="center">
                <v-col>
                    <good-select v-model="goodId" dense/>
                </v-col>
                <v-col cols="auto">
                    <v-btn rounded small color="success" :disabled="!goodId" @click="attach">
                        <v-icon left small>mdi-link-plus</v-icon>
                        Привязать
                    </v-btn>
                </v-col>
            </v-row>
        </v-col>
        <v-col cols="12" md="5">
            <div class="subtitle-2 mt-2">Загружен на площадки</div>
            <v-chip
                v-for="marketplace in value.marketplaces"
                :key="marketplace.id"
                :color="value.is_expired ? 'error' : 'success'"
                :title="value.is_expired ? 'Сертификат просрочен — заменить на площадке!' : ''"
                class="ma-1"
                close
                @click:close="unmark(marketplace)"
            >
                {{ marketplace.name }} {{ marketplace.pivot.uploaded_at | formatDate }}
            </v-chip>
            <v-row dense align="center">
                <v-col>
                    <v-combobox
                        v-model="marketplaceName"
                        :items="marketplaceNames"
                        label="Площадка"
                        dense
                    />
                </v-col>
                <v-col cols="auto">
                    <v-btn rounded small color="success" :disabled="!marketplaceName" @click="mark">
                        <v-icon left small>mdi-upload</v-icon>
                        Отметить
                    </v-btn>
                </v-col>
            </v-row>
        </v-col>
    </v-row>
</template>

<script>
import GoodSelect from "../good/GoodSelect.vue";

export default {
    name: "CertificateGoods",
    components: {GoodSelect},
    props: {
        value: {type: Object, required: true},
    },
    data() {
        return {
            goodId: null,
            marketplaceName: null,
            marketplaceNames: [],
        }
    },
    created() {
        this.$store.dispatch('CERTIFICATE/MARKETPLACES')
            .then((marketplaces) => {
                this.marketplaceNames = marketplaces.map((marketplace) => marketplace.name);
            });
    },
    methods: {
        async attach() {
            await this.$store.dispatch('CERTIFICATE/ATTACH-GOODS', {
                id: this.value.id,
                good_ids: [this.goodId],
            });
            this.goodId = null;
        },
        async detach(certificateGood) {
            await this.$store.dispatch('CERTIFICATE/DETACH-GOOD', {
                id: this.value.id,
                good_id: certificateGood.good_id,
            });
            this.$emit('reload');
        },
        async mark() {
            await this.$store.dispatch('CERTIFICATE/MARK-MARKETPLACE', {
                id: this.value.id,
                name: this.marketplaceName,
            });
            if (this.marketplaceNames.indexOf(this.marketplaceName) < 0) {
                this.marketplaceNames.push(this.marketplaceName);
            }
            this.marketplaceName = null;
        },
        async unmark(marketplace) {
            await this.$store.dispatch('CERTIFICATE/UNMARK-MARKETPLACE', {
                id: this.value.id,
                marketplace_id: marketplace.id,
            });
        },
    }
}
</script>

<style scoped>

</style>
