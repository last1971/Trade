<template>
    <span>
        <v-btn small outlined :loading="busy" :disabled="!ids.length && !codes.length" @click="print">
            <v-icon small left>mdi-printer</v-icon>
            <slot>Печать этикетки</slot>
        </v-btn>
        <!-- Отказы списком: печать бывает частичной, и по каждому коду
             причина своя — чей он в ЧЗ, нет хвоста, ЧЗ его не знает. -->
        <v-dialog v-model="dialog" max-width="760">
            <v-card>
                <v-card-title class="subtitle-1">
                    {{ count ? 'Напечатано ' + count + ', не напечатано ' + failed.length : 'Ничего не напечатано' }}
                </v-card-title>
                <v-simple-table dense>
                    <tbody>
                    <tr v-for="f in failed" :key="f.ki + f.reason">
                        <td class="mono">{{ f.ki === '*' ? '' : f.ki }}</td>
                        <td>{{ f.reason }}</td>
                    </tr>
                    </tbody>
                </v-simple-table>
                <v-card-actions>
                    <v-spacer/>
                    <v-btn text @click="dialog = false">Закрыть</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </span>
</template>

<script>
import FileSaver from "file-saver";

/**
 * Печать этикеток ЧЗ — одна кнопка на список, карточку и скан.
 * ids — наши коды (MARKCODE), codes — отсканированные КМ, которых в базе нет.
 * Что печатать, решает сервер: код должен числиться за нами в Честном знаке.
 */
export default {
    name: "MarkLabelsButton",
    props: {
        ids: {type: Array, default: () => []},
        codes: {type: Array, default: () => []},
    },
    data() {
        return {
            busy: false,
            dialog: false,
            count: 0,
            failed: [],
        };
    },
    methods: {
        print() {
            this.busy = true;
            axios.post('/api/mark-code-labels', {ids: this.ids, codes: this.codes})
                .then(({data}) => {
                    this.count = data.pdf ? data.count : 0;
                    this.failed = data.failed || [];
                    if (data.pdf) {
                        FileSaver.saveAs(this.pdfBlob(data.pdf), data.fileName || 'labels.pdf');
                    }
                    if (this.failed.length) {
                        this.dialog = true;
                    }
                })
                .catch((e) => this.$store.commit('SNACKBAR/ERROR',
                    (e.response && e.response.data && e.response.data.message) || 'Не удалось напечатать', {root: true}))
                .then(() => this.busy = false);
        },
        pdfBlob(base64) {
            const bin = atob(base64);
            const bytes = new Uint8Array(bin.length);
            for (let i = 0; i < bin.length; i++) {
                bytes[i] = bin.charCodeAt(i);
            }
            return new Blob([bytes], {type: 'application/pdf'});
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
