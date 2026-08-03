<template>
    <tr :class="{ 'v-data-table__mobile-table-row' : isMobile }">
        <td v-for="header in headers" :key="header.value"
            :class="{ 'v-data-table__mobile-row' : isMobile }"
        >
            <v-btn v-if="header.value === 'actions' && !isMobile" @click="$emit('reload')" icon>
                <v-icon>mdi-reload</v-icon>
            </v-btn>
            <v-menu v-else-if="header.value === 'CREATED_AT' && has('CREATED_AT')"
                    :close-on-content-click="false"
                    :nudge-right="40"
                    min-width="290px"
                    offset-y
                    transition="scale-transition"
                    v-model="datePicker"
            >
                <template v-slot:activator="{ on }">
                    <v-text-field
                        :value="val('CREATED_AT') ? $options.filters.formatDate(val('CREATED_AT')) : ''"
                        :label="'Позже' + (isMobile ? ' указанной Даты' : '')"
                        readonly
                        clearable
                        @click:clear="set('CREATED_AT', '')"
                        v-on="on"
                    />
                </template>
                <v-date-picker @input="datePicker = false"
                               first-day-of-week="1"
                               :value="val('CREATED_AT')"
                               @change="set('CREATED_AT', $event)"
                />
            </v-menu>
            <v-text-field v-else-if="header.value === 'KI' && has('KI')"
                          :label="isMobile ? 'Код (можно сканером)' : 'Содержит'"
                          :value="val('KI')"
                          :filled="!!val('KI')"
                          @input="set('KI', $event)"
                          @keyup.enter="parseScan"
            />
            <v-select v-else-if="header.value === 'STATUS' && has('STATUS')"
                      :items="statuses"
                      :label="isMobile ? 'Статус' : 'Равен'"
                      :value="val('STATUS')"
                      @input="set('STATUS', $event)"
            />
            <v-select v-else-if="header.value === 'TRANSFER_TYPE' && has('TRANSFER_TYPE')"
                      :items="transferTypes"
                      :label="isMobile ? 'Передан' : 'Равен'"
                      :value="val('TRANSFER_TYPE')"
                      @input="set('TRANSFER_TYPE', $event)"
            />
            <v-text-field v-else-if="header.value === 'invoiceLine.invoice.NS' && has('invoice.NS')"
                          :label="isMobile ? 'Номер счёта' : 'Равен'"
                          :value="val('invoice.NS')"
                          :filled="!!val('invoice.NS')"
                          @input="set('invoice.NS', $event)"
            />
            <v-text-field v-else-if="textAttr(header.value) && has(textAttr(header.value))"
                          :label="isMobile ? header.text : 'Содержит'"
                          :value="val(textAttr(header.value))"
                          :filled="!!val(textAttr(header.value))"
                          @input="set(textAttr(header.value), $event)"
            />
        </td>
    </tr>
</template>

<script>
import markCodeTableMixin from "../../mixins/markCodeTableMixin";
import {extractKi} from "../../helpers/markScan";

// Текстовые фильтры «содержит»: value заголовка → атрибут фильтра на сервере
const TEXT_FILTERS = {
    'good.name.NAME': 'name.NAME',
    'GTIN': 'GTIN',
    'SERIAL_NUMBER': 'SERIAL_NUMBER',
};

export default {
    name: "MarkCodeFilterRow",
    mixins: [markCodeTableMixin],
    props: {
        headers: {
            type: Array,
            required: true,
        },
        options: {
            type: Object,
            required: true,
        },
        isMobile: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            datePicker: false,
        }
    },
    methods: {
        textAttr(headerValue) {
            return TEXT_FILTERS[headerValue];
        },
        idx(attr) {
            return this.options.filterAttributes.indexOf(attr);
        },
        has(attr) {
            return this.idx(attr) >= 0;
        },
        val(attr) {
            return this.options.filterValues[this.idx(attr)];
        },
        set(attr, value) {
            this.$set(this.options.filterValues, this.idx(attr), value === null ? '' : value);
        },
        // Пикнули сканером в поле кода — вытаскиваем чистый KI из сырого скана
        parseScan() {
            try {
                this.set('KI', extractKi(this.val('KI')));
            } catch (e) {
                // не код ЧЗ — оставляем как есть, ищем как текст
            }
        },
    },
}
</script>

<style scoped>

</style>
