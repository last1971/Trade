<template>
    <v-dialog v-model="dialog" max-width="900" scrollable>
        <v-card v-if="current">
            <v-card-title class="d-flex align-center">
                <div>
                    <div>{{ card && card.found ? card.mpn : current.q }}</div>
                    <div class="text-caption grey--text">
                        <span v-if="card && card.found">{{ card.manufacturer.name }}</span>
                        <span v-if="card"> • данные от {{ card.fetchedAt | formatDateTime }}</span>
                        <span v-if="card && card.source === 'cache'"> • из кэша</span>
                    </div>
                </div>
                <v-spacer/>
                <v-btn icon :loading="loading" title="Обновить с mpn.cc" @click="refresh">
                    <v-icon>mdi-refresh</v-icon>
                </v-btn>
                <v-btn icon title="Закрыть" @click="close">
                    <v-icon>mdi-close</v-icon>
                </v-btn>
            </v-card-title>

            <v-card-text v-if="loading" class="text-center py-5">
                <v-progress-circular indeterminate color="primary"/>
            </v-card-text>

            <v-card-text v-else-if="error" class="py-5">
                <div>{{ error.message }}</div>
                <div v-if="error.blockedUntil" class="text-caption grey--text">
                    mpn.cc ограничил запросы, следующая попытка после {{ error.blockedUntil | formatDateTime }}
                </div>
            </v-card-text>

            <!-- Неоднозначно (один MPN у нескольких производителей) или похожее: выбираем производителя. -->
            <v-card-text v-else-if="card && !card.found" class="pa-0">
                <div v-if="!card.candidates.length" class="text-center py-5 grey--text">
                    На mpn.cc такой детали нет
                </div>
                <v-list v-else dense>
                    <v-subheader>Уточните производителя</v-subheader>
                    <v-list-item v-for="candidate in card.candidates"
                                 :key="candidate.url || candidate.mpn + candidate.manufacturer"
                                 @click="select(candidate)"
                    >
                        <v-list-item-content>
                            <v-list-item-title>{{ candidate.mpn }} — {{ candidate.manufacturer }}</v-list-item-title>
                            <v-list-item-subtitle v-if="candidate.category">
                                {{ candidate.category }}
                            </v-list-item-subtitle>
                        </v-list-item-content>
                    </v-list-item>
                </v-list>
            </v-card-text>

            <v-card-text v-else-if="card" class="pa-0">
                <v-simple-table dense>
                    <template v-slot:default>
                        <tbody>
                            <tr v-if="card.description">
                                <td class="grey--text">Описание</td>
                                <td>{{ card.description }}</td>
                            </tr>
                            <tr v-if="card.category">
                                <td class="grey--text">Категория</td>
                                <td>{{ card.category }}</td>
                            </tr>
                            <tr v-if="card.lifecycle">
                                <td class="grey--text">Статус выпуска</td>
                                <td>{{ card.lifecycle }}</td>
                            </tr>
                            <tr v-if="card.package">
                                <td class="grey--text">Корпус</td>
                                <td>{{ card.package }}</td>
                            </tr>
                            <tr v-if="card.tnvedHint">
                                <td class="grey--text">Подсказка ТНВЭД</td>
                                <td>{{ card.tnvedHint }} <span class="text-caption grey--text">— первые 6 знаков, проверить по классификатору</span></td>
                            </tr>
                            <tr v-for="key in complianceKeys" :key="key">
                                <td class="grey--text">{{ complianceLabels[key] }}</td>
                                <td>{{ card.compliance[key] }}</td>
                            </tr>
                            <tr v-for="parameter in card.parameters" :key="parameter.key">
                                <td class="grey--text">{{ parameter.label }}</td>
                                <td>{{ parameter.value }}</td>
                            </tr>
                        </tbody>
                    </template>
                </v-simple-table>

                <v-divider v-if="card.datasheets.length"/>
                <v-list v-if="card.datasheets.length" dense>
                    <v-subheader>Даташиты</v-subheader>
                    <v-list-item v-for="datasheet in card.datasheets" :key="datasheet.url"
                                 :href="datasheet.url" target="_blank"
                    >
                        <v-list-item-icon><v-icon>mdi-file-pdf-box</v-icon></v-list-item-icon>
                        <v-list-item-content>
                            <v-list-item-title>
                                {{ datasheet.isPrimary ? 'Основной' : 'Дополнительный' }}
                            </v-list-item-title>
                        </v-list-item-content>
                    </v-list-item>
                </v-list>

                <v-divider v-if="card.alternatives.length"/>
                <v-list v-if="card.alternatives.length" dense>
                    <v-subheader>Аналоги</v-subheader>
                    <v-list-item v-for="alternative in card.alternatives" :key="alternative.mpn + alternative.manufacturer">
                        <v-list-item-content>
                            <v-list-item-title>{{ alternative.mpn }} — {{ alternative.manufacturer }}</v-list-item-title>
                            <v-list-item-subtitle v-if="alternative.relationship">
                                {{ alternative.relationship }}
                            </v-list-item-subtitle>
                        </v-list-item-content>
                    </v-list-item>
                </v-list>
            </v-card-text>

            <v-card-actions v-if="card && card.url">
                <v-spacer/>
                <v-btn text :href="card.url" target="_blank">Открыть на mpn.cc</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
import { mapGetters } from 'vuex';

export default {
    name: "MpnCardDialog",
    data() {
        return {
            complianceLabels: {
                rohs: 'RoHS',
                reach: 'REACH',
                eccn: 'ECCN',
                hts: 'HTS (США)',
                taric: 'TARIC (ЕС)',
                countryOfOrigin: 'Страна происхождения',
            },
        }
    },
    computed: {
        ...mapGetters({
            card: 'MPN/CARD',
            current: 'MPN/CURRENT',
            loading: 'MPN/IS_LOADING',
            error: 'MPN/ERROR',
        }),
        dialog: {
            get() {
                return this.$store.getters['MPN/IS_OPEN'];
            },
            set(v) {
                if (!v) this.close();
            }
        },
        complianceKeys() {
            if (!this.card || !this.card.compliance) return [];
            return Object.keys(this.complianceLabels).filter((key) => this.card.compliance[key]);
        },
    },
    methods: {
        close() {
            this.$store.commit('MPN/CLOSE');
        },
        refresh() {
            this.$store.dispatch('MPN/REFRESH');
        },
        /** Выбор производителя среди кандидатов: slug берём из ответа сервиса, не сочиняем. */
        select(candidate) {
            this.$store.dispatch('MPN/OPEN_CARD', {
                q: candidate.mpn,
                manufacturer: candidate.manufacturerSlug,
            });
        },
    },
}
</script>
