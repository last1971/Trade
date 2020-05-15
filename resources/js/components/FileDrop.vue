<template>
    <v-sheet
        @click="$refs.input.click()"
        @dragenter.prevent="dragover = true"
        @dragleave.prevent="dragover = false"
        @dragover.prevent="dragover = true"
        @drop.prevent="drop"
        @keypress.enter="$refs.input.click()"
        color="indigo lighten-4"
        style="cursor:pointer;"
        tabindex="0"
        title="Click to grap a file from your PC!"
    >
        <input
            @change="changed"
            accept="application/excel"
            ref="input"
            style="display: none"
            type="file"
        />
        <v-row justify="center">
            <v-icon
                color="indigo darken-2"
                size="75"
                v-if="!dragover"
            >
                mdi-cloud-upload-outline
            </v-icon>
            <v-icon
                color="indigo darken-2"
                size="75"
                v-else
            >
                mdi-book-plus
            </v-icon>
        </v-row>
        <v-row justify="center" v-if="text">
            <span class="title indigo--text text--darken-2">{{ text }}</span>
        </v-row>
    </v-sheet>
</template>

<script>
    export default {
        name: "FileDrop",
        props: {
            text: {
                type: String,
                default: 'Drag\'n drop or click to upload file!'
            }
        },
        data() {
            return {
                dragover: false,
                formUpload: false,
            }
        },
        methods: {
            drop(e) {
                this.filesSelected(e.dataTransfer);
            },
            changed(e) {
                if (e.target.files) this.filesSelected(e.target.files)
            },
            filesSelected(fileList) {
                this.$emit('filesSelected', fileList);
                this.dragover = false;
            }
        }
    }
</script>

<style scoped>

</style>
