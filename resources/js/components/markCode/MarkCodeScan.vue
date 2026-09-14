<template>
    <v-text-field
        ref="field"
        v-model="scan"
        :loading="busy"
        :error-messages="error"
        :hint="hint"
        persistent-hint
        dense
        outlined
        clearable
        autofocus
        prepend-inner-icon="mdi-barcode-scan"
        label="Отсканируйте код или вставьте его из буфера"
        @keyup.enter="find"
        @click:clear="error = ''"
    >
        <template v-slot:append-outer>
            <v-btn small text :disabled="busy" @click="find">Найти</v-btn>
        </template>
    </v-text-field>
</template>

<script>
/**
 * Поиск кода маркировки по скану. Сканер отдаёт КМ целиком — с криптохвостом,
 * скобками или разделителями групп, — поэтому разбирает его сервер (KmReader),
 * а тут только поле и переход в карточку.
 *
 * Живёт и на списке марок, и в самой карточке: со сканером в руках коды смотрят
 * подряд, и уходить за этим на другую страницу незачем.
 */
export default {
    name: "MarkCodeScan",
    data() {
        return {
            scan: '',
            error: '',
            busy: false,
        };
    },
    computed: {
        hint() {
            // Про раскладку сказано заранее: испорченный раскладкой код внешне
            // неотличим от настоящего, а в базе такого нет — и человек ищет
            // проблему не там.
            return 'Enter — открыть карточку. Код регистрозависим: сканируйте в латинской раскладке, с выключенным Caps Lock.';
        },
    },
    methods: {
        find() {
            const scan = (this.scan || '').trim();
            this.error = '';
            if (!scan) {
                return;
            }
            this.busy = true;
            axios.get('/api/mark-code-find', {params: {scan}})
                .then(({data}) => {
                    if (!data.found) {
                        this.error = data.message;
                        return;
                    }
                    this.scan = '';
                    // Уже открытая карточка другого кода — обычное дело: со сканером
                    // коды смотрят один за другим, и переход должен её перерисовать.
                    if (this.$route.name !== 'mark-code' || String(this.$route.params.id) !== String(data.id)) {
                        this.$router.push({name: 'mark-code', params: {id: data.id}});
                    }
                })
                .catch((e) => this.error = (e.response && e.response.data && e.response.data.message)
                    || 'Не удалось найти код')
                .then(() => {
                    this.busy = false;
                    // Фокус обратно в поле: следующий скан должен попасть туда же.
                    this.$nextTick(() => this.$refs.field && this.$refs.field.focus());
                });
        },
    },
};
</script>
