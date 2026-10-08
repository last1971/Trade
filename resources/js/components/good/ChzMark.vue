<template>
    <!-- Режим товара: при наведении — GTIN, карточка НК, ТН ВЭД и остаток без кодов -->
    <v-menu v-if="show && byCode" open-on-hover offset-y :close-on-content-click="false" @input="load">
        <template v-slot:activator="{ on, attrs }">
            <v-icon small color="grey" class="ml-1" v-bind="attrs" v-on="on">{{ icon.name }}</v-icon>
        </template>
        <v-card class="pa-2 caption">
            <div class="font-weight-bold">{{ icon.title }}</div>
            <div v-if="!info">загрузка…</div>
            <template v-else>
                <div v-for="row in info.gtins" :key="row.ID">
                    <template v-if="row.GTIN">
                        GTIN {{ row.GTIN }} ({{ row.IS_PRIMARY ? 'наш' : 'поставщика' }})
                        <span v-if="row.NK_STATE" :class="nkStateColor(row.NK_STATE) + '--text'">
                            — карточка НК: {{ nkStateText(row.NK_STATE) }}
                        </span>
                    </template>
                    <template v-else>GTIN нет</template>
                </div>
                <div v-if="classif">
                    ТН ВЭД {{ classif.TNVED }}<span v-if="classif.OKPD2"> · ОКПД2 {{ classif.OKPD2 }}</span>
                </div>
                <div v-if="info.uncovered" :class="info.uncovered.total > 0 ? 'red--text' : ''">
                    Без кодов на остатке: {{ info.uncovered.total }} шт
                </div>
            </template>
        </v-card>
    </v-menu>
    <v-icon v-else-if="show" small color="grey" class="ml-1" :title="icon.title">{{ icon.name }}</v-icon>
</template>

<script>
import {chzIcon} from "../../store/marking";
import marking from "../../mixins/marking";

// Значок «товар подлежит маркировке ЧЗ». Логика (загрузка кодов, проверка) —
// в сторе MARKING; здесь только отображение, единое для всех мест.
// Приоритет режимов: flag → count → code. Подсказка при наведении — только в режиме товара:
// у строки поставщика и у документа нашего товара за значком нет.
export default {
    name: "ChzMark",
    mixins: [marking],
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
    data() {
        return {
            // Подсказка грузится при первом наведении: {gtins, uncovered}.
            info: null,
        };
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
        // ТН ВЭД/ОКПД2 — с первой строки, где они есть (наш GTIN идёт первым).
        classif() {
            return this.info ? this.info.gtins.find((row) => row.TNVED) || null : null;
        },
    },
    methods: {
        load(opened) {
            if (!opened || this.info) return;
            Promise.all([
                this.$store.dispatch('MARKING/GOOD_GTINS', this.code).catch(() => []),
                this.$store.dispatch('MARKING/GOOD_UNCOVERED', this.code).catch(() => null),
            ]).then(([gtins, uncovered]) => {
                this.info = {gtins, uncovered};
            });
        },
    },
}
</script>
