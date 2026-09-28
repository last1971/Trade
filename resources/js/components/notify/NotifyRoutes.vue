<template>
    <v-card flat>
        <v-card-title class="subtitle-1 py-2">
            Маршруты уведомлений
            <v-spacer/>
            <notify-route-add @reload="updateItems"/>
            <v-btn small icon :loading="loading" title="Обновить" class="ml-2" @click="updateItems">
                <v-icon>mdi-refresh</v-icon>
            </v-btn>
        </v-card-title>
        <v-card-subtitle class="py-1">
            Тема — кто реагирует: OPS (склад/менеджеры), MARKING (Честный знак), PRICES (цены и остатки),
            FINANCE, DEV. Эти же строки читает ozon. Тема без включённых строк уходит в никуда.
        </v-card-subtitle>
        <v-divider/>
        <v-data-table
            :headers="headers"
            :items="items"
            :loading="loading"
            :loading-text="loadingText"
            :options.sync="options"
            :server-items-length="total"
            :footer-props="{showFirstLastPage: true}"
            item-key="ID"
            dense
        >
            <template v-slot:item.TOPIC="{ item }">
                <edit-field @save="save" attribute="TOPIC" :rules="[rules.required, topicRule]" v-model="item">
                    <template v-slot:cell><b>{{ item.TOPIC }}</b></template>
                </edit-field>
            </template>
            <template v-slot:item.CHANNEL="{ item }">
                <v-chip x-small :color="item.CHANNEL === 'matrix' ? 'indigo' : 'blue-grey'" dark>
                    {{ channelText(item.CHANNEL) }}
                </v-chip>
            </template>
            <template v-slot:item.TARGET="{ item }">
                <edit-field @save="save" attribute="TARGET" :rules="[rules.required, targetRule(item)]" v-model="item">
                    <template v-slot:cell><span class="mono">{{ item.TARGET }}</span></template>
                </edit-field>
            </template>
            <template v-slot:item.ENABLED="{ item }">
                <v-simple-checkbox
                    :value="item.ENABLED === 1"
                    :ripple="false"
                    @input="toggle(item, $event)"
                />
            </template>
            <template v-slot:item.actions="{ item }">
                <v-btn x-small text :loading="busy === item.ID" title="Пробное сообщение адресату" @click="test(item)">
                    <v-icon small left>mdi-send-check</v-icon>
                    тест
                </v-btn>
                <v-btn x-small icon color="error" title="Удалить" @click="deleteItem(item)">
                    <v-icon small>mdi-delete</v-icon>
                </v-btn>
            </template>
        </v-data-table>
    </v-card>
</template>

<script>
import tableMixin from "../../mixins/tableMixin";
import utilsMixin from "../../mixins/utilsMixin";
import EditField from "../EditField.vue";
import NotifyRouteAdd from "./NotifyRouteAdd.vue";
import {CHANNELS, MATRIX_ROOM_RE, TOPIC_RE} from "./notifyRules";

export default {
    name: "NotifyRoutes",
    components: {EditField, NotifyRouteAdd},
    mixins: [tableMixin, utilsMixin],
    data() {
        return {
            model: 'NOTIFY-ROUTE',
            options: {
                sortBy: ['TOPIC', 'CHANNEL'],
                sortDesc: [false, false],
            },
            busy: null,
        }
    },
    methods: {
        channelText: (v) => (CHANNELS.find((c) => c.value === v) || {text: v}).text,
        topicRule: (v) => TOPIC_RE.test(v || '') || 'Только латиница и _',
        targetRule(item) {
            return (v) => item.CHANNEL === 'matrix'
                ? (MATRIX_ROOM_RE.test(v || '') || 'Нужен id комнаты вида !xxx:elcopro.ru')
                : (/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v || '') || 'Нужен адрес почты');
        },
        toggle(item, value) {
            this.save({...item, ENABLED: value ? 1 : 0});
        },
        deleteItem(item) {
            this.$store.dispatch(this.model + '/REMOVE', item.ID)
                .then(() => this.updateItems())
                .catch(this.error);
        },
        error(e) {
            const data = e && e.response ? e.response.data : {};
            this.$store.commit('SNACKBAR/ERROR', data.message || 'Ошибка запроса', {root: true});
        },
        // Регулярка не докажет, что бот в комнате, — только живая отправка.
        test(item) {
            this.busy = item.ID;
            axios.post('/api/notify-route/' + item.ID + '/test')
                .then(({data}) => this.$store.commit(data.ok ? 'SNACKBAR/SUCCESS' : 'SNACKBAR/ERROR', data.message, {root: true}))
                .catch(this.error)
                .then(() => this.busy = null);
        },
    },
    beforeRouteEnter(to, from, next) {
        next(vm => {
            vm.$store.commit('BREADCRUMBS/SET', [
                {text: 'Торговля', to: {name: 'home'}, exact: true, disabled: false},
                {text: 'Маршруты уведомлений', to: {name: 'notify-route'}, exact: true, disabled: true},
            ]);
        });
    }
}
</script>

<style scoped>
.mono {
    font-family: monospace;
}
</style>
