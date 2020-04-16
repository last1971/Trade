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
                  item-key="ID"
                  loading-text="Loading... Please wait"
                  v-if="onRoles"
    >
        <template v-slot:item.roles="{ item }">
            <role-select :multiple="true" :value="roles(item)"></role-select>
        </template>
    </v-data-table>
</template>

<script>
    import tableMixin from "../mixins/tableMixin";
    import utilsMixin from "../mixins/utilsMixin";
    import RoleSelect from "./RoleSelect";
    import _ from "lodash";

    export default {
        name: "Users",
        components: {RoleSelect},
        mixins: [tableMixin, utilsMixin],
        data() {
            return {
                options: {
                    with: ['roles', 'employee'],
                },
                mobileFiltersVisible: false,
                onRoles: false,
            }
        },
        created() {
            const items = this.$store.getters['ROLE/ALL'];
            if (_.isEmpty(items)) {
                this.$store.dispatch('ROLE/ALL')
                    .then(() => this.onRoles = true)
            } else {
                this.onRoles = true;
            }
        },
        methods: {
            roles(item) {
                return item.roles.map((v) => v.id);
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
                        text: 'Пользователи',
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
