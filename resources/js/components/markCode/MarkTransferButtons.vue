<template>
    <div class="d-flex align-center" v-if="visible">
        <v-btn :loading="loading"
               :title="`Коды ${label} переданы — пометить вручную`"
               @click="mark"
               class="mt-2"
               fab
        >
            <v-icon color="success">mdi-check-bold</v-icon>
        </v-btn>
        <v-btn :loading="loading"
               :title="`Откатить передачу кодов ${label}`"
               @click="unmark"
               class="mt-2 ml-2"
               fab
        >
            <v-icon color="red">mdi-undo</v-icon>
        </v-btn>
    </div>
</template>

<script>
    import {worksWithChz} from "../../helpers/marking";

    /**
     * Ручная пометка кодов маркировки документа как переданных покупателю.
     * Одна кнопка на оба документа: счёт (УПД-2 маркетплейсу) и УПД (юрлицу).
     * Каким видом передачи это ляжет в MARKCODES, решает бэкенд по типу документа.
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
            }
        },
        computed: {
            visible() {
                return worksWithChz(this.buyer);
            },
            label() {
                return this.document === 'invoice' ? 'УПД-2' : 'УПД';
            },
        },
        methods: {
            mark() {
                this.send(
                    'mark-as-transferred',
                    `Пометить коды ${this.label} как переданные? Используй, если XML отдан в ЭДО/ЛК маркетплейса вне системы.`,
                    (count) => `Помечено ${count} кодов как переданные`
                );
            },
            unmark() {
                this.send(
                    'unmark-as-transferred',
                    `Откатить передачу кодов ${this.label}? Коды вернутся в оборот.`,
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
                }
            },
        },
    }
</script>

<style scoped>

</style>
