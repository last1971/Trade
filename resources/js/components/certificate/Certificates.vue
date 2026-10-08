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
            <v-card class="d-flex flex-row align-center ma-2">
                <v-card-actions>
                    <certificate-add @reload="updateItems" />
                </v-card-actions>
                <good-select v-model="goodFilter" can-empty class="mx-4" style="max-width: 600px"/>
            </v-card>
        </template>
        <template v-slot:body.prepend="{ isMobile }">
            <tr :class="{ 'v-data-table__mobile-table-row' : isMobile }">
                <td v-if="!isMobile"></td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Номер' : 'Содержит'"
                                  v-model="options.filterValues[0]"
                                  :filled="!!options.filterValues[0]"
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-select :items="types"
                              :label="isMobile ? 'Тип' : 'Равен'"
                              v-model="options.filterValues[1]"
                              clearable
                    />
                </td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Название' : 'Содержит'"
                                  v-model="options.filterValues[2]"
                                  :filled="!!options.filterValues[2]"
                    />
                </td>
                <td v-for="index in [3, 4]" :key="index" :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-menu :close-on-content-click="false"
                            min-width="290px"
                            offset-y
                            transition="scale-transition"
                    >
                        <template v-slot:activator="{ on }">
                            <v-text-field :value="options.filterValues[index] ? $options.filters.formatDate(options.filterValues[index]) : ''"
                                          :label="index === 3 ? 'Позже' : 'Раньше'"
                                          prepend-icon="mdi-calendar-edit"
                                          readonly
                                          clearable
                                          @click:clear="$set(options.filterValues, index, '')"
                                          v-on="on"
                            />
                        </template>
                        <v-date-picker first-day-of-week="1"
                                       v-model="options.filterValues[index]"
                        />
                    </v-menu>
                </td>
                <td v-if="!isMobile"></td>
                <td :class="{ 'v-data-table__mobile-row' : isMobile }">
                    <v-text-field :label="isMobile ? 'Примечание' : 'Содержит'"
                                  v-model="options.filterValues[5]"
                                  :filled="!!options.filterValues[5]"
                    />
                </td>
                <td v-if="!isMobile"></td>
            </tr>
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
import tableOptionsRouteMixin from "../../mixins/tableOptionsRouteMixin";
import EditField from "../EditField.vue";
import CertificateAdd from "./CertificateAdd.vue";
import CertificateGoods from "./CertificateGoods.vue";
import GoodSelect from "../good/GoodSelect.vue";

export default {
    name: "Certificates",
    components: {GoodSelect, CertificateGoods, CertificateAdd, EditField},
    mixins: [tableMixin, tableOptionsRouteMixin, utilsMixin],
    data() {
        return {
            options: {
                filterAttributes: ['number', 'type', 'name', 'date_from', 'date_to', 'remark', 'certificateGoods.good_id'],
                filterOperators: ['CONTAIN', '=', 'CONTAIN', '>=', '<=', 'CONTAIN', '='],
                filterValues: ['', '', '', '', '', '', ''],
            },
            model: 'CERTIFICATE',
            types: [],
        }
    },
    created() {
        this.$store.dispatch(this.model + '/TYPES').then((types) => this.types = types);
    },
    computed: {
        // Фильтр по товару ищем по имени, а не по позиции: опции из URL/localStorage
        // могут быть сохранены до появления фильтра — тогда дописываем его в конец.
        goodFilter: {
            get() {
                const index = (this.options.filterAttributes || []).indexOf('certificateGoods.good_id');
                return index < 0 ? null : (this.options.filterValues[index] || null);
            },
            set(val) {
                const index = (this.options.filterAttributes || []).indexOf('certificateGoods.good_id');
                if (index < 0) {
                    this.$set(this.options, 'filterAttributes', _.concat(this.options.filterAttributes || [], 'certificateGoods.good_id'));
                    this.$set(this.options, 'filterOperators', _.concat(this.options.filterOperators || [], '='));
                    this.$set(this.options, 'filterValues', _.concat(this.options.filterValues || [], val || ''));
                } else {
                    this.$set(this.options.filterValues, index, val || '');
                }
            },
        },
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
