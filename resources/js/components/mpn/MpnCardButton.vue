<template>
    <v-btn v-if="queryable" icon x-small title="Справка mpn.cc" @click.stop="open">
        <v-icon small>mdi-information-outline</v-icon>
    </v-btn>
</template>

<script>
import { isMpnQueryable } from '../../helpers/mpn';

export default {
    name: "MpnCardButton",
    props: {
        // Строка прайса: берём name как MPN. Производителя не подставляем —
        // в прайсе он текстом («Yageo»), а mpn.cc ждёт свой slug; slug приходит
        // только из его же ответа, когда человек выбирает кандидата.
        item: {
            type: Object,
            required: true,
        },
    },
    computed: {
        queryable() {
            return isMpnQueryable(this.item.name);
        },
    },
    methods: {
        open() {
            this.$store.dispatch('MPN/OPEN_CARD', {q: this.item.name});
        },
    },
}
</script>
