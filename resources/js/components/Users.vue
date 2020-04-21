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
                  v-if="go"
    >
        <template v-slot:item.name="{ item }">
            <v-text-field @change="save(item)" v-model="item.name"/>
        </template>
        <template v-slot:item.employeeId="{ item }">
            <employee-select @input="save(item)" v-model="item.employeeId"/>
        </template>
        <template v-slot:item.roles="{ item }">
            <role-select :multiple="true" @input="save(item)" v-model="item.roles"></role-select>
        </template>
        <template v-slot:item.userBuyers="{ item }">
            <user-buyers @input="save(item)" v-model="item.user_buyers"/>
        </template>
        <template v-slot:item.userFirms="{ item }">
            <user-firms @input="save(item)" v-model="item.user_firms"/>
        </template>
    </v-data-table>
</template>

<script>
    import tableMixin from "../mixins/tableMixin";
    import utilsMixin from "../mixins/utilsMixin";
    import RoleSelect from "./RoleSelect";
    import _ from "lodash";
    import EmployeeSelect from "./EmployeeSelect";
    import UserBuyers from "./UserBuyers";
    import UserFirms from "./UserFirms";

    export default {
        name: "Users",
        components: {UserFirms, UserBuyers, EmployeeSelect, RoleSelect},
        mixins: [tableMixin, utilsMixin],
        data() {
            return {
                options: {
                    with: ['roles', 'employee', 'userBuyers.buyer', 'userFirms.firm'],
                },
                mobileFiltersVisible: false,
                model: 'USER',
                go: false,
            }
        },
        created() {
            Promise.all(
                ['ROLE', 'FIRM', 'EMPLOYEE'].map((v) => _.isEmpty(this.$store.getters[v + '/ALL']) ?
                    this.$store.dispatch(v + '/ALL', {}) :
                    Promise.resolve()
                )
            ).then(() => this.go = true);
        },
        methods: {
            save(item) {
                this.$store.dispatch('USER/UPDATE', {item, options: this.options})
                    .then(() => {
                    })
                    .catch(() => {
                        const index = _.indexOf(this.items, {id: item.id});
                        const oldValue = this.$store.getters['USER/GET'](item.id);
                        this.items.splice(index, 1, oldValue);
                    })
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
