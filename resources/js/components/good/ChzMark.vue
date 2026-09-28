<template>
    <v-icon small color="grey" class="ml-1" :title="icon.title" v-if="show">{{ icon.name }}</v-icon>
</template>

<script>
import {chzIcon} from "../../store/marking";

// Значок «товар подлежит маркировке ЧЗ». Логика (загрузка кодов, проверка) —
// в сторе MARKING; здесь только отображение, единое для всех мест.
// Приоритет режимов: flag → count → code.
export default {
    name: "ChzMark",
    props: {
        // Режим поставщика: готовый признак из прайса (options.marking).
        // null — поставщик признак не прислал, решают остальные режимы.
        flag: {
            type: Boolean,
            default: null,
        },
        // Режим товара: проверка GOODSCODE по списку маркируемых.
        code: {
            type: [Number, String],
            default: null,
        },
        // Режим документа: готовый счётчик маркируемых строк (агрегат markGoodLinesCount).
        count: {
            type: [Number, String],
            default: null,
        },
    },
    created() {
        // Коды маркируемых товаров тянутся один раз за сессию (no-op после загрузки).
        if (this.byCode) this.$store.dispatch('MARKING/FETCH_GOODS');
    },
    computed: {
        icon() {
            return chzIcon;
        },
        byCode() {
            return this.flag === null && this.count === null && this.code !== null;
        },
        show() {
            if (this.flag !== null) return this.flag;
            if (this.count !== null) return this.count > 0;
            return this.byCode && this.$store.getters[chzIcon.getter](this.code);
        },
    },
}
</script>
