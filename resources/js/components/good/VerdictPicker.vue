<template>
    <div class="d-flex flex-wrap align-center" style="gap: 12px">
        <v-combobox
            v-model="rawTnved"
            :items="tnvedItems"
            label="ТНВЭД (выбор или ввод)"
            :hint="tnvedHint(tnvedCode)"
            persistent-hint
            dense clearable
            :style="{ maxWidth: fieldWidth }"
            @change="onTnvedChange"
        />
        <v-combobox
            v-model="rawOkpd2"
            :items="okpd2Items(tnvedCode)"
            label="ОКПД2 (выбор или ввод)"
            dense hide-details clearable
            :style="{ maxWidth: fieldWidth }"
            @change="emitInput"
        />
        <v-chip small outlined :color="markRequired ? 'orange' : 'green'">
            {{ markRequired ? 'подлежит' : 'не подлежит' }}
        </v-chip>
    </div>
</template>

<script>
import marking from "../../mixins/marking";

// Единый редактор вердикта ТНВЭД/ОКПД2 (+ чип «подлежит») — для карточки и
// массового тулбара. Вся вёрстка и логика в одном месте, дублей нет.
// v-model — объект { tnved, okpd2 } (нормализованные коды).
export default {
    name: "VerdictPicker",
    mixins: [marking],
    props: {
        value: {
            type: Object,
            default: () => ({tnved: '', okpd2: ''}),
        },
        fieldWidth: {
            type: String,
            default: '340px',
        },
    },
    data() {
        return {
            rawTnved: this.value.tnved || '',
            rawOkpd2: this.value.okpd2 || '',
        };
    },
    computed: {
        // v-combobox отдаёт объект при выборе из списка и строку при вводе (mixin).
        tnvedCode() {
            return this.normCode(this.rawTnved);
        },
        okpd2Code() {
            return this.normCode(this.rawOkpd2);
        },
        markRequired() {
            return this.isMarkRequired(this.tnvedCode);
        },
    },
    watch: {
        // Родитель поменял значение (загрузка карточки / подбор / сброс) — отразить.
        value(v) {
            if ((v.tnved || '') !== this.tnvedCode) {
                this.rawTnved = v.tnved || '';
            }
            if ((v.okpd2 || '') !== this.okpd2Code) {
                this.rawOkpd2 = v.okpd2 || '';
            }
            this.resolveTnved(this.tnvedCode);
        },
    },
    mounted() {
        this.resolveTnved(this.tnvedCode);
    },
    methods: {
        // Смена ТНВЭД: авто-ОКПД2 (один вариант) + расшифровка в подстрочник.
        onTnvedChange() {
            this.rawOkpd2 = this.defaultOkpd2(this.tnvedCode);
            this.resolveTnved(this.tnvedCode);
            this.emitInput();
        },
        emitInput() {
            this.$emit('input', {tnved: this.tnvedCode, okpd2: this.okpd2Code});
        },
    },
}
</script>
