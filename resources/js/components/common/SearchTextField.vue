<template>
    <v-text-field
        v-bind="$attrs"
        v-on="$listeners"
        :value="value"
        @input="$emit('input', $event)"
    >
        <template v-slot:append>
            <v-btn icon x-small @click="clear" v-if="value" title="Очистить">
                <v-icon small>mdi-eraser</v-icon>
            </v-btn>
            <v-btn icon x-small @click="pasteFromClipboard" title="Вставить из буфера">
                <v-icon small>mdi-content-paste</v-icon>
            </v-btn>
        </template>
    </v-text-field>
</template>

<script>
export default {
    name: "SearchTextField",
    props: {
        value: {
            type: String,
            default: ''
        }
    },
    methods: {
        clear() {
            this.$emit('input', '');
        },
        async pasteFromClipboard() {
            try {
                const text = await navigator.clipboard.readText();
                this.$emit('input', text.trim());
            } catch (err) {
                console.error('Failed to read clipboard:', err);
            }
        }
    }
}
</script>
