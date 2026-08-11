<template>
    <v-icon small color="grey" class="ml-1" :title="icon.title" v-if="show">{{ icon.name }}</v-icon>
</template>

<script>
import {chzIcon} from "../../store/marking";

// Значок «товар подлежит маркировке ЧЗ» по GOODSCODE. Логика (загрузка кодов,
// проверка) — в сторе MARKING; здесь только отображение, единое для всех мест.
export default {
    name: "ChzMark",
    props: {
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
        if (this.code !== null) this.$store.dispatch('MARKING/FETCH_GOODS');
    },
    computed: {
        icon() {
            return chzIcon;
        },
        show() {
            return this.count !== null
                ? this.count > 0
                : this.$store.getters[chzIcon.getter](this.code);
        },
    },
}
</script>
