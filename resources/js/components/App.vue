<template>
    <v-app>
        <v-navigation-drawer
            app
            v-if="user"
            v-model="drawer"
        >
            <v-list dense>
                <v-list-item :key="menu.id" :to="menu.to" link v-for="menu in menus">
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
            <v-toolbar-title>Trade</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-btn @click="logout" icon v-if="user">
                <v-icon>mdi-logout</v-icon>
            </v-btn>
        </v-app-bar>

        <v-content>
            <v-container
                class="fill-height"
                fluid
            >
                <router-view></router-view>
            </v-container>
        </v-content>
        <v-footer
            app
            color="primary"
        >
            <span class="white--text">&copy; 2020</span>
        </v-footer>
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
                {id: 2, text: 'Помощь', to: {name: 'help', icon: 'mdi-help'}}
            ]
        }),
        computed: {
            ...mapGetters({
                user: 'USER/GET',
            })
        },
        methods: {
            logout() {
                this.$store.dispatch('USER/LOGOUT')
                    .then(() => this.$router.push({name: 'login'}))
            }
        }
    }
</script>

<style scoped>

</style>
