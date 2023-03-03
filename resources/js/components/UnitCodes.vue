<template>
    <v-data-table
                  :headers="headers"
                  :items="items"
                  :loading="loading"
                  :multi-sort="true"
                  :options.sync="options"
                  :server-items-length="total"
                  item-key="id"
    >
        <template v-slot:top>
            <v-card class="d-flex flex-row ma-2">
                <v-card-actions>
                    <unit-code-add @reload="updateItems" />
                </v-card-actions>
            </v-card>
        </template>
        <template v-slot:item.code="{ item }">
            <edit-field @save="save" attribute="code" v-model="item"/>
        </template>
        <template v-slot:item.name="{ item }">
            <edit-field @save="save" attribute="name" v-model="item"/>
        </template>
    </v-data-table>
</template>

<script>
import tableMixin from "../mixins/tableMixin";
import utilsMixin from "../mixins/utilsMixin";
import EditField from "./EditField.vue";
import UnitCodeAdd from "./UnitCodeAdd.vue";

export default {
    name: "UnitCodes",
    components: {UnitCodeAdd, EditField},
    mixins: [tableMixin, utilsMixin],
    data() {
        return {
            options: {},
            mobileFiltersVisible: false,
            model: 'UNIT-CODE',
            editing: false,
            itemValue: 0,
            itemKey: 'id',
        }
    },
    methods: {
        editItem(item) {
            this.itemValue = item[this.itemKey];
            this.editing = true;
        },
        deleteItem(item) {
            this.$store.dispatch(this.model + '/REMOVE', item[this.itemKey])
                .then(() => this.updateItems());
        }
    },
    beforeRouteEnter(to, from, next) {
        next(vm => {
            vm.$store.commit('BREADCRUMBS/SET', [
                {
                    text: 'Торговля',
                    to: {name: 'home'},
                    exact: true,
                    disabled: false,
                },
                {
                    text: 'Ед.изм.',
                    to: {name: 'unit-code'},
                    exact: true,
                    disabled: true,
                }
            ]);
        });
    }
}
</script>

<style scoped>

</style>
