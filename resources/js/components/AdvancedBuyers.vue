<template>
    <v-data-table :footer-props="{
            showFirstLastPage: true,
        }"
                  :headers="headers"
                  :items="items"
                  :loading="loading"
                  :multi-sort="true"
                  :options.sync="options"
                  :server-items-length="total"
                  item-key="id"
                  loading-text="Loading... Please wait"
    >
        <template v-slot:header.actions>
            <advanced-buyer-edit :item-value="itemValue"
                                 @close="itemValue=0"
                                 @saved="updateItems"
                                 v-model="editing"
            />
        </template>
        <template v-slot:item.actions="{ item }">
            <v-speed-dial :open-on-hover="true" direction="right">
                <template v-slot:activator>
                    <v-btn icon>
                        <v-icon color="white">mdi-format-list-bulleted</v-icon>
                    </v-btn>
                </template>
                <v-btn @click="editItem(item)" fab>
                    <v-icon color="green">mdi-pencil</v-icon>
                </v-btn>
                <v-btn @click="deleteItem(item)" fab>
                    <v-icon color="red">mdi-delete</v-icon>
                </v-btn>
            </v-speed-dial>
        </template>
    </v-data-table>
</template>

<script>
    import tableMixin from "../mixins/tableMixin";
    import utilsMixin from "../mixins/utilsMixin";
    import AdvancedBuyerEdit from "./AdvancedBuyerEdit";

    export default {
        name: "AdvancedBuyers",
        components: {AdvancedBuyerEdit},
        mixins: [tableMixin, utilsMixin],
        data() {
            return {
                options: {
                    with: ['buyer',],
                },
                mobileFiltersVisible: false,
                model: 'ADVANCED-BUYER',
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
                        text: 'Покупатели+',
                        to: {name: 'users'},
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
