<template>
    <v-data-table
        hide-default-footer
        :headers="headers2"
        :items="items"
        :loading="loading"
        :multi-sort="true"
        :options.sync="options"
        :server-items-length="total"
        :loading-text="loadingText"
        show-select
        v-model="selected"
        item-key="id"
    >
        <template v-slot:top>
            <v-card>
                <v-card-actions>
                    <v-text-field
                        label="Новый алиас"
                        single-line
                        v-model="newAlias"
                    >
                        <template v-slot:prepend>
                            <v-btn fab small :disabled="!newAlias" @click="addAlias">
                                <v-icon color="green">mdi-content-save</v-icon>
                            </v-btn>
                        </template>
                    </v-text-field>
                    <v-btn :disabled="!canBeDeleted" rounded color="error" class="ml-2" @click="remove">
                        <v-icon left>mdi-delete</v-icon>
                        УДАЛИТЬ
                    </v-btn>
                </v-card-actions>
            </v-card>
        </template>
        <template v-slot:item.name="{ item }">
            <edit-field @save="save" attribute="name" v-model="item"/>
        </template>
    </v-data-table>
</template>

<script>
import tableMixin from "../../mixins/tableMixin";
import utilsMixin from "../../mixins/utilsMixin";
import EditField from "../EditField.vue";

export default {
    name: "UnitCodeAlias",
    components: {EditField},
    mixins: [tableMixin, utilsMixin],
    props: {
        value: {
            type: Object,
            required: true,
        }
    },
    data() {
        return {
            options: {
                filterAttributes: [
                    'unit_code_id',
                ],
                filterOperators: ['='],
                filterValues: [this.value.id],
                itemsPerPage: 100,
            },
            mobileFiltersVisible: false,
            dependent: true,
            model: 'UNIT-CODE-ALIAS',
            selected: [],
            newAlias: null,
        }
    },
    computed: {
        canBeDeleted() {
            return this.selected.length;
        },
        headers2() {
            return this.headers.filter((h) => !h.additional);
        }
    },
    watch: {
        value() {
            this.options.filterValues = [this.value.id];
            this.updateItems();
        }
    },
    methods: {
        reload() {
            this.updateItems();
            this.$emit('reload');
        },
        async remove() {
            try {
                await Promise.all(this.selected.map((item) => {
                    return this.$store.dispatch('UNIT-CODE-ALIAS/REMOVE', item.id);
                }));
                this.selected = [];
                this.updateItems();
                this.$emit('reload');
            } catch (e) {
                console.error(e);
            }
        },
        async addAlias() {
            try {
                const newAlias = { name: this.newAlias, unit_code_id: this.value.id }
                await this.$store.dispatch('UNIT-CODE-ALIAS/CREATE', {item: newAlias});
                this.newAlias = null;
                this.reload();
            } catch (e) {
                console.error(e);
            }
        }
    },
}
</script>

<style scoped>

</style>
