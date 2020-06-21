<template>
    <v-data-table
        :headers="headers"
        :items="value"
        item-key="id"
        :items-per-page="-1"
        hide-default-footer
    >
        <template v-slot:item.GOODSCODE="{ item }">
            <good-select :new-search="item.searchName" :ref="'ac_' + item.id" v-model="item.GOODSCODE"/>
        </template>
        <template v-slot:item.name="{ item }">
            <v-btn @click="goodFocus(item)" outlined v-if="!item.GOODSCODE">
                {{ item.name }}
            </v-btn>
            <div v-else>{{ item.name }}</div>
        </template>
        <template v-slot:item.price="{ item }">{{ item.price | formatRub }}</template>
        <template v-slot:item.amount="{ item }">{{ item.amount | formatRub }}</template>
        <template v-slot:footer>
            <v-row class="py-4" justify="center" no-gutters>
                <v-col lg="1">
                    <v-btn @click="cancel" class="pa-2">
                        <v-icon color="red">mdi-cancel</v-icon>
                        Отмена
                    </v-btn>
                </v-col>
                <v-col class="pl-6" lg="1">
                    <v-btn :disabled="!savePossible" @click="save" class="pa-2">
                        <v-icon color="green">mdi-content-save</v-icon>
                        Сохранить
                    </v-btn>
                </v-col>
            </v-row>
        </template>
    </v-data-table>
</template>

<script>
    import GoodSelect from "./GoodSelect";

    export default {
        name: "OrderImportLines",
        components: {GoodSelect},
        props: {
            value: {
                type: Array,
            },
            masterId: {
                type: Number,
            }
        },
        computed: {
            headers() {
                return this.$store.getters['ORDER-IMPORT-LINE/HEADERS'];
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
                this.$store.dispatch(
                    'ORDER-IMPORT-LINE/SAVE', {lines: this.value, masterId: this.masterId}
                )
                    .then(() => this.$emit('input', []));
            }
        }
    }
</script>

<style scoped>

</style>
