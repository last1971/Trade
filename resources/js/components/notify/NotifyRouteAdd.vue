<template>
    <v-dialog v-model="adding" max-width="560">
        <template v-slot:activator="{ on }">
            <v-btn small rounded color="success" v-on="on">
                <v-icon left small>mdi-plus</v-icon>
                Добавить маршрут
            </v-btn>
        </template>
        <v-card>
            <v-card-title class="subtitle-1">
                Новый маршрут
                <v-spacer/>
                <v-btn icon @click="close"><v-icon color="red">mdi-close</v-icon></v-btn>
            </v-card-title>
            <v-divider/>
            <v-card-text>
                <v-combobox
                    v-model="route.TOPIC"
                    :items="topics"
                    label="Тема"
                    hint="Свои темы тоже можно, но их должен знать код"
                    persistent-hint
                    :rules="[rules.required, topicRule]"
                />
                <v-select v-model="route.CHANNEL" :items="channels" label="Канал" :rules="[rules.required]"/>
                <v-text-field
                    v-model="route.TARGET"
                    :label="route.CHANNEL === 'matrix' ? 'Id комнаты' : 'Адрес почты'"
                    :placeholder="route.CHANNEL === 'matrix' ? '!abcdef:elcopro.ru' : 'name@elcopro.ru'"
                    :hint="route.CHANNEL === 'matrix' ? 'Element → настройки комнаты → Дополнительно → внутренний ID' : ''"
                    persistent-hint
                    :rules="[rules.required, targetRule]"
                />
            </v-card-text>
            <v-card-actions class="d-flex justify-end">
                <v-btn rounded color="success" :disabled="!valid" :loading="saving" @click="save">
                    <v-icon left>mdi-content-save</v-icon>
                    Сохранить
                </v-btn>
                <v-btn rounded color="error" @click="close">
                    <v-icon left>mdi-close</v-icon>
                    Отменить
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
import utilsMixin from "../../mixins/utilsMixin";
import {CHANNELS, MATRIX_ROOM_RE, TOPIC_RE, TOPICS} from "./notifyRules";

const empty = () => ({TOPIC: null, CHANNEL: 'matrix', TARGET: null, ENABLED: 1});

export default {
    name: "NotifyRouteAdd",
    mixins: [utilsMixin],
    data() {
        return {
            adding: false,
            saving: false,
            route: empty(),
            topics: TOPICS,
            channels: CHANNELS,
        }
    },
    computed: {
        valid() {
            return this.rules.required(this.route.TOPIC) === true
                && this.topicRule(this.route.TOPIC) === true
                && this.rules.required(this.route.TARGET) === true
                && this.targetRule(this.route.TARGET) === true;
        }
    },
    methods: {
        topicRule: (v) => TOPIC_RE.test(v || '') || 'Только латиница и _',
        targetRule(v) {
            return this.route.CHANNEL === 'matrix'
                ? (MATRIX_ROOM_RE.test(v || '') || 'Нужен id комнаты вида !xxx:elcopro.ru')
                : (/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v || '') || 'Нужен адрес почты');
        },
        error(e) {
            const data = e && e.response ? e.response.data : {};
            this.$store.commit('SNACKBAR/ERROR', data.message || 'Ошибка запроса', {root: true});
        },
        close() {
            this.adding = false;
        },
        async save() {
            this.saving = true;
            try {
                await this.$store.dispatch('NOTIFY-ROUTE/CREATE', {item: {...this.route, TOPIC: (this.route.TOPIC || '').toUpperCase()}});
                this.route = empty();
                this.$emit('reload');
                this.close();
            } catch (e) {
                this.error(e);
            } finally {
                this.saving = false;
            }
        }
    }
}
</script>
