<template>
    <v-container class="px-0">
        <v-row
            align="center"
            justify="center"
        >
            <v-col
                cols="8"
            >
                <v-card class="elevation-12">
                    <v-toolbar
                        :color="notAlarm ? 'primary' : 'red'"
                        dark
                    >
                        <v-toolbar-title v-if="notAlarm">Измените пароль</v-toolbar-title>
                        <v-toolbar-title v-else>Что-то пошло не так</v-toolbar-title>
                    </v-toolbar>
                    <v-card-text class="px-4">
                        <v-form v-if="notAlarm">
                            <v-text-field
                                :error-messages="error.email"
                                label="E-mail"
                                name="login"
                                prepend-icon="mdi-email"
                                type="text"
                                v-model="user.email"
                            />
                            <v-text-field
                                :error-messages="error.password"
                                label="Пароль"
                                name="password"
                                prepend-icon="mdi-lock"
                                type="password"
                                v-model="user.password"
                            />
                            <v-text-field
                                :error-messages="error.password_confirmation"
                                label="Подтверждение пароля"
                                name="password"
                                prepend-icon="mdi-lock"
                                type="password"
                                v-model="user.password_confirmation"
                            />
                        </v-form>
                        <div align="center" class="headline" v-else>
                            {{ error.token[0] }}
                            <v-btn :to="{ name: 'home'}" icon>
                                <v-icon>mdi-arrow-left-circle</v-icon>
                            </v-btn>
                        </div>
                    </v-card-text>
                    <v-card-actions class="px-4 pb-4" v-if="notAlarm">
                        <v-spacer/>
                        <v-btn @click="register" color="primary">
                            <v-icon left>mdi-key-change</v-icon>
                            Изменить
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
    import authMixin from "../mixins/authMixin";

    export default {
        name: "ResetPassword",
        mixins: [authMixin],
        data() {
            return {
                user: {
                    email: '',
                    password: '',
                    password_confirmation: '',
                    token: '',
                },
                error: {},
                notAlarm: true,
            }
        },
        mounted() {
            this.$store.dispatch('AUTH/CHECK', this.$route.params.token)
                .then((response) => this.user = response.data)
                .catch((error) => {
                    this.error = error.response.data.errors;
                    this.notAlarm = false;
                })
        },
        methods: {
            register() {
                this.$store.dispatch('AUTH/RESET', this.user)
                    .then(() => this.$router.push({name: 'home'}))
                    .catch((error) => {
                        this.error = error.response.data.errors;
                    });
            }
        }
    }
</script>

<style scoped>

</style>
