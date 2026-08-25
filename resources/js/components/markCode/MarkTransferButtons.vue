<template>
    <div class="d-flex align-center" v-if="state.available">
        <v-btn v-if="canMark"
               :loading="loading"
               :title="`Коды ${label} переданы — пометить вручную (${state.total} шт.)`"
               @click="mark"
               class="mt-2"
               fab
        >
            <v-icon color="success">mdi-check-bold</v-icon>
        </v-btn>
        <v-btn v-else-if="canUnmark"
               :loading="loading"
               :title="`Откатить передачу кодов ${label} (${state.total} шт.)`"
               @click="unmark"
               class="mt-2"
               fab
        >
            <v-icon color="red">mdi-undo</v-icon>
        </v-btn>
        <v-btn v-else
               :title="`Передана часть кодов: ${state.transferred} из ${state.total} — разбирайся вручную`"
               class="mt-2"
               disabled
               fab
        >
            <v-icon>mdi-alert</v-icon>
        </v-btn>
    </div>
</template>

<script>
    import {worksWithChz} from "../../helpers/marking";

    const nothingToShow = () => ({available: false, reason: null, total: 0, transferred: 0});

    /**
     * Ручная пометка кодов маркировки документа как переданных покупателю.
     * Одна кнопка на оба документа: счёт (УПД-2 маркетплейсу) и УПД (юрлицу).
     *
     * Что показывать, решает бэкенд ({@see MarkCodeTransferService::state}):
     * помечать нечего — кнопок нет; коды свободны — только зелёная; коды
     * переданы — только красная. Двух доступных кнопок разом не бывает.
     */
    export default {
        name: "MarkTransferButtons",
        props: {
            document: {
                type: String,
                required: true,
                validator: (v) => ['invoice', 'transfer-out'].includes(v),
            },
            documentId: {
                type: [Number, String],
                required: true,
            },
            buyer: {
                type: Object,
                default: null,
            },
        },
        data() {
            return {
                loading: false,
                state: nothingToShow(),
            }
        },
        computed: {
            label() {
                return this.document === 'invoice' ? 'УПД-2' : 'УПД';
            },
            canMark() {
                return this.state.transferred === 0;
            },
            canUnmark() {
                return this.state.total > 0 && this.state.transferred === this.state.total;
            },
        },
        watch: {
            // Карточка может доехать по частям: и id, и покупатель тянут перечитывание.
            documentId: {
                immediate: true,
                handler() {
                    this.loadState();
                },
            },
            buyer() {
                this.loadState();
            },
        },
        methods: {
            async loadState() {
                // Покупателя знаем и так — не дёргаем бэкенд там, где ЧЗ вообще ни при чём.
                if (!this.documentId || !worksWithChz(this.buyer)) {
                    this.state = nothingToShow();
                    return;
                }
                try {
                    const {data} = await window.axios.get('/api/mark-codes/transfer-state', {
                        params: {document: this.document, document_id: this.documentId},
                    });
                    this.state = data;
                } catch (e) {
                    this.state = nothingToShow();
                }
            },
            mark() {
                this.send(
                    'mark-as-transferred',
                    `Пометить коды ${this.label} как переданные (${this.state.total} шт.)? Используй, если XML отдан в ЭДО/ЛК маркетплейса вне системы.`,
                    (count) => `Помечено ${count} кодов как переданные`
                );
            },
            unmark() {
                this.send(
                    'unmark-as-transferred',
                    `Откатить передачу кодов ${this.label} (${this.state.total} шт.)? Коды вернутся в оборот.`,
                    (count) => `Откачено ${count} кодов`
                );
            },
            async send(action, confirmText, successText) {
                if (!confirm(confirmText)) return;
                this.loading = true;
                try {
                    const {data} = await window.axios.post(`/api/mark-codes/${action}`, {
                        document: this.document,
                        document_id: this.documentId,
                    });
                    this.$store.commit("SNACKBAR/PUSH", {
                        text: successText(data.count),
                        color: "success",
                        status: true,
                        timeout: 10000,
                    });
                    this.$emit('changed');
                } catch (e) {
                    this.$store.commit(
                        "SNACKBAR/ERROR",
                        e.response?.data?.message || `Ошибка пометки кодов ${this.label}`
                    );
                } finally {
                    this.loading = false;
                    await this.loadState();
                }
            },
        },
    }
</script>

<style scoped>

</style>
