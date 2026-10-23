<template>
    <div>
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
        <!-- Кода нет в нашей базе: что о нём говорит Честный знак, и можно ли
             напечатать — наш ли он там и есть ли в скане криптохвост. -->
        <v-alert v-if="foreign" dense outlined :type="foreignOurs ? 'info' : 'warning'" class="mt-2 mb-0">
            <div>Кода нет в нашей базе: <span class="mono">{{ foreign.ki }}</span></div>
            <div v-if="foreign.error">Честный знак: {{ foreign.error }}</div>
            <template v-else-if="foreign.code && foreign.code.known">
                <div>
                    Честный знак: {{ foreign.code.productName || '—' }}, GTIN {{ foreign.code.gtin }},
                    статус {{ foreign.code.status }}
                </div>
                <div>
                    Владелец: {{ (foreign.code.raw && foreign.code.raw.ownerName) || '—' }}
                    <span v-if="foreign.code.ownerInn">({{ foreign.code.ownerInn }})</span>
                    <b v-if="!foreignOurs"> — не наш, печатать нельзя</b>
                </div>
            </template>
            <div v-else-if="!busy">Честный знак такой код не знает</div>
            <div v-if="foreignOurs && !foreign.km">
                В скане нет криптохвоста — для печати отсканируйте саму этикетку
            </div>
            <mark-labels-button v-if="foreignOurs && foreign.km" class="mt-1" :codes="[foreign.km]"/>
        </v-alert>
    </div>
</template>

<script>
import {extractKi, extractKm} from "../../helpers/markScan";
import MarkLabelsButton from "./MarkLabelsButton";

/**
 * Поиск кода маркировки по скану. Сканер отдаёт КМ целиком — с криптохвостом,
 * скобками или разделителем групп, — поэтому КИ достаёт extractKi, та же
 * читалка, что разбирает скан в фильтре списка и в подборе.
 *
 * Живёт и на списке марок, и в самой карточке: со сканером в руках коды смотрят
 * подряд, и уходить за этим на другую страницу незачем.
 */
export default {
    name: "MarkCodeScan",
    components: {MarkLabelsButton},
    data() {
        return {
            scan: '',
            error: '',
            busy: false,
            // Код не из нашей базы: КИ, КМ для печати (null — хвоста нет) и ответ ЧЗ.
            foreign: null,
        };
    },
    computed: {
        // Наш ли код, решает chz-сервис (поле ours), здесь только читаем.
        foreignOurs() {
            return !!(this.foreign && this.foreign.code && this.foreign.code.ours);
        },
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
            this.foreign = null;
            if (!scan) {
                return;
            }

            let ki, km;
            try {
                ki = extractKi(scan);
                km = extractKm(scan);
            } catch (e) {
                // Читалка объясняет отказ словами («некорректная длина», «нет AI 01») —
                // своего текста не выдумываем.
                this.error = e.message;
                return;
            }

            this.busy = true;
            axios.get('/api/mark-code-find', {params: {ki}})
                .then(({data}) => {
                    if (!data.found) {
                        return this.askChz(ki, km);
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
                .then(() => this.done());
        },
        // Чужой по происхождению код: в нашей базе его нет, спрашиваем ЧЗ напрямую.
        askChz(ki, km) {
            this.foreign = {ki, km};
            this.scan = '';
            return axios.get('/api/mark-code-chz-info', {params: {code: ki}})
                .then(({data}) => this.foreign = {ki, km, code: data.code, error: data.ok ? '' : data.error})
                .catch(() => this.foreign = {ki, km, error: 'не ответил'});
        },
        done() {
            this.busy = false;
            // Фокус обратно в поле: следующий скан должен попасть туда же.
            this.$nextTick(() => this.$refs.field && this.$refs.field.focus());
        },
    },
};
</script>

<style scoped>
.mono {
    font-family: monospace;
    font-size: 12px;
    word-break: break-all;
}
</style>
