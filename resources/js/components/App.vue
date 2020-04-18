<template>
    <v-app>
        <v-navigation-drawer
            app
            v-if="user && hasPermission('nav')"
            v-model="drawer"
        >
            <v-list dense nav>
                <v-list-item
                    :key="menu.id"
                    :to="menu.to"
                    link
                    v-for="menu in menus" v-if="hasPermission('nav.' + menu.to.name)"
                >
                    <v-list-item-action>
                        <v-icon>{{ menu.icon }}</v-icon>
                    </v-list-item-action>
                    <v-list-item-content>
                        <v-list-item-title>{{ menu.text }}</v-list-item-title>
                    </v-list-item-content>
                </v-list-item>
            </v-list>
        </v-navigation-drawer>

        <v-app-bar
            app
            color="primary"
            dark
        >
            <v-app-bar-nav-icon @click.stop="drawer = !drawer"/>
            <v-toolbar-title>
                <v-breadcrumbs :items="breadcrumbs" large>
                    <template v-slot:item="{ item }">
                        <v-breadcrumbs-item :to="item.to" v-if="item.disabled">
                            {{ item.text }}
                        </v-breadcrumbs-item>
                        <v-breadcrumbs-item v-else>
                            <router-link :disabled="item.disabled" :to="item.to" class="white--text">
                                {{ item.text }}
                            </router-link>
                        </v-breadcrumbs-item>
                    </template>
                </v-breadcrumbs>
            </v-toolbar-title>
            <v-spacer></v-spacer>
            <v-tooltip bottom v-if="user">
                <template v-slot:activator="{ on }">
                    <v-btn @click="$router.back()" icon v-on="on">
                        <v-icon>mdi-arrow-left-circle</v-icon>
                    </v-btn>
                </template>
                <span>Вернуться</span>
            </v-tooltip>
            <v-chip outlined v-if="user && !$vuetify.breakpoint.xsOnly">
                {{ user.name }}
            </v-chip>
            <v-tooltip bottom v-if="user">
                <template v-slot:activator="{ on }">
                    <v-btn @click="logout" icon v-on="on">
                        <v-icon>mdi-exit-to-app</v-icon>
                    </v-btn>
                </template>
                <span>Выход</span>
            </v-tooltip>
        </v-app-bar>

        <v-content>
            <transition>
                <keep-alive>
                    <router-view></router-view>
                </keep-alive>
            </transition>
        </v-content>
        <v-footer
            app
            color="primary"
        >
            <span class="white--text">ООО "ЭлкоПро" &copy; 2020</span>
        </v-footer>
        <v-snackbar
            :color="snackbar.color"
            :multi-line="snackbar.multi"
            :timeout="snackbar.timeout"
            @input="$store.commit('SNACKBAR/SHIFT')"
            v-model="snackbar.status"
        >
            {{ snackbar.text }}
            <v-btn
                @click="closeSnackbar"
                dark
                text
            >
                Закрыть
            </v-btn>
        </v-snackbar>
    </v-app>
</template>

<script>
    import {mapGetters} from 'vuex';

    export default {
        name: "App",
        props: {
            source: String,
        },
        data: () => ({
            drawer: null,
            menus: [
                {id: 1, text: 'Домой', to: {name: 'home'}, icon: 'mdi-home'},
                {id: 2, text: 'Счета', to: {name: 'invoices'}, icon: 'mdi-text-box'},
                {id: 3, text: 'Исх.УПД', to: {name: 'transfer-outs'}, icon: 'mdi-clipboard-text-play'},
                {id: 4, text: 'Заказы', to: {name: 'orders'}, icon: 'mdi-clipboard-arrow-left'},
                {id: 5, text: 'Пользователи', to: {name: 'users'}, icon: 'mdi-account-multiple'},
            ]
        }),
        computed: {
            ...mapGetters({
                user: 'AUTH/GET',
                hasPermission: 'AUTH/HAS_PERMISSION',
                snackbar: 'SNACKBAR/GET',
            }),
            breadcrumbs() {
                const breadcrumbs = this.$store.getters['BREADCRUMBS/ALL'];
                if (this.$vuetify.breakpoint.xsOnly) {
                    return [_.last(breadcrumbs)];
                }
                return breadcrumbs;
            }
        },
        methods: {
            logout() {
                this.$router.replace({name: 'home'})
                    .catch(() => {
                    })
                    .then(() => {
                        this.$store.dispatch('AUTH/LOGOUT')
                            .then(() => {
                                this.$store.commit('BREADCRUMBS/SET', []);
                                this.$router.push({name: 'login'});
                                this.$destroy();
                                window.location.reload();
                            })
                    })
            },
            closeSnackbar() {
                this.$store.commit('SNACKBAR/STATUS', false);
                this.$store.commit('SNACKBAR/SHIFT');
            }
        }
    }
</script>

<style scoped>

</style>
