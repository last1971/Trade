<template>
    <v-data-table
        :headers="headers"
        :items="value"
        item-key="id"
        :items-per-page="-1"
        hide-default-footer
        dense
    >
        <template v-slot:header.actions>
            <select-headers :model="model"/>
        </template>
        <template v-slot:item.GOODSCODE="{ item }">
            <good-select :new-search="item.searchName" :ref="'ac_' + item.id" v-model="item.GOODSCODE"/>
        </template>
        <template v-slot:item.name="{ item }">
            <v-tooltip bottom v-if="!item.GOODSCODE">
                <template v-slot:activator="{ on, attrs }">
                    <v-btn @click="goodFocus(item)" outlined v-bind="attrs" v-on="on">
                        < в поиск
                    </v-btn>
                </template>
                <span>{{ item.name }}</span>
            </v-tooltip>
            <div v-else>{{ item.name }}</div>
        </template>
        <template v-slot:item.price="{ item }">{{ item.price | formatRub }}</template>
        <template v-slot:item.amount="{ item }">{{ item.amount | formatRub }}</template>
        <template v-slot:footer>
            <v-row class="py-4" justify="center" no-gutters>
                <v-col md="1">
                    <v-btn :disabled="loading" @click="cancel" class="pa-2">
                        <v-icon color="red">mdi-cancel</v-icon>
                        Отмена
                    </v-btn>
                </v-col>
                <v-col class="pl-6" md="1">
                    <v-btn :disabled="!savePossible || loading" :loading="loading" @click="save" class="pa-2">
                        <v-icon color="green">mdi-content-save</v-icon>
                        Сохранить
                    </v-btn>
                </v-col>
            </v-row>
        </template>
    </v-data-table>
</template>

<script>
    import GoodSelect from "../good/GoodSelect";
    import SelectHeaders from "../SelectHeaders";

    export default {
        name: "OrderImportLines",
        components: {SelectHeaders, GoodSelect},
        props: {
            value: {
                type: Array,
            },
            masterId: {
                type: Number,
            }
        },
        data() {
            return {
                loading: false,
                model: 'ORDER-IMPORT-LINE',
            }
        },
        computed: {
            headers() {
                return this.$store.getters['ORDER-IMPORT-LINE/HEADERS'].filter((header) => !header.hidden);
            },
            savePossible() {
                return this.value.reduce((res, val) => res && !!val.GOODSCODE, true);
            }
        },
        methods: {
            goodFocus(item) {
                const el = this.$refs['ac_' + item.id].$el;
                let childData = el.querySelectorAll("input")[0];
                childData.focus();
                this.$set(item, 'searchName', item.name);
            },
            cancel() {
                this.$emit('input', []);
            },
            save() {
                this.loading = true;
                this.$store.dispatch(
                    'ORDER-IMPORT-LINE/SAVE', {lines: this.value, masterId: this.masterId}
                )
                    .then(() => this.$emit('input', []))
                    .catch(() => {
                    })
                    .then(() => this.loading = false);
            }
        }
    }
</script>

<style scoped>

</style>
