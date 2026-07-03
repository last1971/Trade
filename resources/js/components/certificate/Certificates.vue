<template>
    <v-data-table
                  :headers="headers"
                  :items="items"
                  :loading="loading"
                  :multi-sort="true"
                  :options.sync="options"
                  :server-items-length="total"
                  :item-class="itemClass"
                  item-key="id"
                  :single-expand="true"
                  show-expand
    >
        <template v-slot:top>
            <v-card class="d-flex flex-row ma-2">
                <v-card-actions>
                    <certificate-add @reload="updateItems" />
                </v-card-actions>
            </v-card>
        </template>
        <template v-slot:item.number="{ item }">
            <edit-field @save="save" attribute="number" v-model="item"/>
        </template>
        <template v-slot:item.type="{ item }">
            <edit-field @save="save" attribute="type" v-model="item"/>
        </template>
        <template v-slot:item.name="{ item }">
            <edit-field @save="save" attribute="name" v-model="item"/>
        </template>
        <template v-slot:item.date_from="{ item }">
            <v-menu offset-y>
                <template v-slot:activator="{ on }">
                    <span v-on="on" style="cursor:pointer" title="Изменить дату">
                        {{ item.date_from ? $options.filters.formatDate(item.date_from) : '—' }}
                    </span>
                </template>
                <v-date-picker
                    :value="item.date_from"
                    @input="saveDate(item, 'date_from', $event)"
                    first-day-of-week="1"
                    show-adjacent-months
                />
            </v-menu>
        </template>
        <template v-slot:item.date_to="{ item }">
            <v-menu offset-y>
                <template v-slot:activator="{ on }">
                    <span v-on="on" style="cursor:pointer" title="Изменить дату">
                        {{ item.date_to ? $options.filters.formatDate(item.date_to) : '—' }}
                        <v-icon v-if="item.is_expired" color="error" small title="Срок действия истёк!">
                            mdi-alert
                        </v-icon>
                    </span>
                </template>
                <v-date-picker
                    :value="item.date_to"
                    @input="saveDate(item, 'date_to', $event)"
                    first-day-of-week="1"
                    show-adjacent-months
                />
            </v-menu>
        </template>
        <template v-slot:item.marketplaces="{ item }">
            <v-chip
                v-for="marketplace in item.marketplaces"
                :key="marketplace.id"
                :color="item.is_expired ? 'error' : 'success'"
                :title="item.is_expired ? 'Сертификат просрочен — заменить на площадке!' : ''"
                class="ma-1"
                small
                outlined
            >
                {{ marketplace.name }}
            </v-chip>
        </template>
        <template v-slot:item.remark="{ item }">
            <edit-field @save="save" attribute="remark" v-model="item"/>
        </template>
        <template v-slot:item.actions="{ item }">
            <v-btn icon small @click="openItem(item)" title="Открыть файл">
                <v-icon small>mdi-eye</v-icon>
            </v-btn>
            <v-btn icon small @click="downloadItem(item)" title="Скачать файл">
                <v-icon small>mdi-download</v-icon>
            </v-btn>
            <v-btn icon small @click="deleteItem(item)" title="Удалить сертификат">
                <v-icon color="red" small>mdi-delete</v-icon>
            </v-btn>
        </template>
        <template v-slot:expanded-item="{ headers, item }">
            <td :colspan="headers.length" :key="item.id">
                <v-card flat>
                    <certificate-goods v-model="item" @reload="updateItems" class="my-4"/>
                </v-card>
            </td>
        </template>
    </v-data-table>
</template>

<script>
import tableMixin from "../../mixins/tableMixin";
import utilsMixin from "../../mixins/utilsMixin";
import EditField from "../EditField.vue";
import CertificateAdd from "./CertificateAdd.vue";
import CertificateGoods from "./CertificateGoods.vue";

export default {
    name: "Certificates",
    components: {CertificateGoods, CertificateAdd, EditField},
    mixins: [tableMixin, utilsMixin],
    data() {
        return {
            options: {},
            model: 'CERTIFICATE',
        }
    },
    methods: {
        requestParams() {
            return _.assign({}, this.options, {
                with: ['certificateGoods.good.name', 'marketplaces'],
            });
        },
        itemClass(item) {
            return item.is_expired ? 'certificate-expired' : '';
        },
        async save(item) {
            await this.$store.dispatch(this.model + '/UPDATE', {item, options: this.requestParams()});
        },
        saveDate(item, attribute, value) {
            const changed = _.cloneDeep(item);
            changed[attribute] = value;
            this.save(changed);
        },
        openItem(item) {
            this.$store.dispatch(this.model + '/OPEN', item);
        },
        downloadItem(item) {
            this.$store.dispatch(this.model + '/DOWNLOAD', item);
        },
        deleteItem(item) {
            this.$store.dispatch(this.model + '/REMOVE', item.id)
                .then(() => this.updateItems());
        }
    },
    beforeRouteEnter(to, from, next) {
        next(vm => {
            vm.$store.commit('BREADCRUMBS/SET', [
                {
                    text: 'Торговля',
                    to: {name: 'home'},
                    exact: true,
                    disabled: false,
                },
                {
                    text: 'Сертификаты',
                    to: {name: 'certificates'},
                    exact: true,
                    disabled: true,
                }
            ]);
        });
    }
}
</script>

<style>
.certificate-expired td {
    background-color: rgba(255, 72, 66, 0.16);
}
</style>
