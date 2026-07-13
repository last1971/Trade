<template>
    <v-card outlined class="my-4">
        <v-card-title class="subtitle-2 py-2">Национальный каталог (GTIN)</v-card-title>
        <v-divider/>
        <v-simple-table dense>
            <template v-slot:default>
                <thead>
                <tr>
                    <th>GTIN</th>
                    <th>ТНВЭД</th>
                    <th>ОКПД2</th>
                    <th>ИНН поставщика</th>
                    <th>Осн.</th>
                    <th>Кодов ЧЗ</th>
                    <th style="width: 110px"></th>
                </tr>
                </thead>
                <tbody>
                <tr v-if="!rows.length && !adding">
                    <td colspan="7" class="text-center">Отсутствуют данные</td>
                </tr>
                <tr v-for="row in rows" :key="row.ID">
                    <template v-if="editId === row.ID">
                        <td>
                            <v-text-field v-model="form.GTIN" dense hide-details/>
                        </td>
                        <td>
                            <v-text-field v-model="form.TNVED" dense hide-details/>
                        </td>
                        <td>
                            <v-text-field v-model="form.OKPD2" dense hide-details/>
                        </td>
                        <td>
                            <v-text-field v-model="form.SUPPLIER_INN" dense hide-details/>
                        </td>
                        <td>{{ row.IS_PRIMARY ? 'да' : '' }}</td>
                        <td>{{ row.mark_codes_count }}</td>
                        <td>
                            <v-btn icon small @click="saveEdit(row)" title="Сохранить">
                                <v-icon small color="green">mdi-content-save</v-icon>
                            </v-btn>
                            <v-btn icon small @click="editId = null" title="Отмена">
                                <v-icon small color="red">mdi-close</v-icon>
                            </v-btn>
                        </td>
                    </template>
                    <template v-else>
                        <td>{{ row.GTIN }}</td>
                        <td>{{ row.TNVED }}</td>
                        <td>{{ row.OKPD2 }}</td>
                        <td>{{ row.SUPPLIER_INN }}</td>
                        <td>{{ row.IS_PRIMARY ? 'да' : '' }}</td>
                        <td>{{ row.mark_codes_count }}</td>
                        <td>
                            <v-btn icon small
                                   :disabled="notEditable || row.mark_codes_count > 0"
                                   @click="startEdit(row)"
                                   :title="row.mark_codes_count > 0 ? 'Есть коды Честного знака — менять нельзя' : 'Редактировать'"
                            >
                                <v-icon small>mdi-pencil</v-icon>
                            </v-btn>
                            <v-btn icon small
                                   :disabled="notEditable || row.mark_codes_count > 0"
                                   @click="remove(row)"
                                   :title="row.mark_codes_count > 0 ? 'Есть коды Честного знака — удалять нельзя' : 'Удалить'"
                            >
                                <v-icon small color="red">mdi-delete</v-icon>
                            </v-btn>
                        </td>
                    </template>
                </tr>
                <tr v-if="adding">
                    <td>
                        <v-text-field v-model="form.GTIN" dense hide-details placeholder="GTIN"/>
                    </td>
                    <td>
                        <v-text-field v-model="form.TNVED" dense hide-details placeholder="ТНВЭД"/>
                    </td>
                    <td>
                        <v-text-field v-model="form.OKPD2" dense hide-details placeholder="ОКПД2"/>
                    </td>
                    <td>
                        <v-text-field v-model="form.SUPPLIER_INN" dense hide-details placeholder="ИНН"/>
                    </td>
                    <td></td>
                    <td></td>
                    <td>
                        <v-btn icon small @click="saveNew" title="Сохранить">
                            <v-icon small color="green">mdi-content-save</v-icon>
                        </v-btn>
                        <v-btn icon small @click="adding = false" title="Отмена">
                            <v-icon small color="red">mdi-close</v-icon>
                        </v-btn>
                    </td>
                </tr>
                </tbody>
            </template>
        </v-simple-table>
        <v-card-actions v-if="!adding && !notEditable">
            <v-btn small text color="green" @click="startAdd">
                <v-icon small left>mdi-plus</v-icon>
                Добавить GTIN
            </v-btn>
        </v-card-actions>
    </v-card>
</template>

<script>
export default {
    name: "GoodGtins",
    props: {
        value: {type: [Number, String], required: true},
    },
    data() {
        return {
            rows: [],
            adding: false,
            editId: null,
            form: {GTIN: '', TNVED: '', OKPD2: '', SUPPLIER_INN: ''},
        }
    },
    computed: {
        notEditable() {
            return !this.$store.getters['AUTH/HAS_PERMISSION']('good.update');
        },
    },
    watch: {
        value: {
            immediate: true,
            handler() {
                this.load();
            }
        }
    },
    methods: {
        load() {
            if (!this.value) {
                this.rows = [];
                return;
            }
            axios.get('/api/good/' + this.value + '/gtins')
                .then((response) => {
                    this.rows = response.data;
                })
                .catch(() => {
                    this.rows = [];
                });
        },
        startAdd() {
            this.editId = null;
            this.form = {GTIN: '', TNVED: '', OKPD2: '', SUPPLIER_INN: ''};
            this.adding = true;
        },
        startEdit(row) {
            this.adding = false;
            this.form = {
                GTIN: row.GTIN,
                TNVED: row.TNVED,
                OKPD2: row.OKPD2,
                SUPPLIER_INN: row.SUPPLIER_INN,
            };
            this.editId = row.ID;
        },
        saveNew() {
            axios.post('/api/good/' + this.value + '/gtins', this.form)
                .then(() => {
                    this.adding = false;
                    this.load();
                })
                .catch((e) => this.error(e));
        },
        saveEdit(row) {
            axios.put('/api/good-gtin/' + row.ID, this.form)
                .then(() => {
                    this.editId = null;
                    this.load();
                })
                .catch((e) => this.error(e));
        },
        remove(row) {
            if (!confirm('Удалить GTIN ' + row.GTIN + '?')) return;
            axios.delete('/api/good-gtin/' + row.ID)
                .then(() => this.load())
                .catch((e) => this.error(e));
        },
        error(e) {
            const response = e.response ? e.response.data : {};
            const message = response.errors
                ? Object.values(response.errors).flat().join(' ')
                : (response.message || 'Ошибка');
            this.$store.commit('SNACKBAR/ERROR', message, {root: true});
        },
    }
}
</script>

<style scoped>

</style>
