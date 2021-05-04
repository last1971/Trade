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
                        color="primary"
                        dark

                    >
                        <v-toolbar-title>Вход</v-toolbar-title>
                    </v-toolbar>
                    <v-card-text class="px-4">
                        <v-form @submit="login" v-if="notForgot">
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
                                @keypress.enter="login"
                            />
                        </v-form>
                        <div class="headline" v-else>
                            Сcылка на восстановление пароля отправлена на Вашу электронную почту
                        </div>
                    </v-card-text>
                    <v-card-actions class="px-4 pb-4" v-if="notForgot">
                        <v-spacer/>
                        <v-btn :loading="loginLoading" @click="login" color="primary" type="submit">
                            <v-icon left>mdi-login-variant</v-icon>
                            Войти
                        </v-btn>
                        <v-spacer/>
                        <v-btn :loading="forgotLoading" @click="forgot" color="secondary">
                            <v-icon left>mdi-lock-question</v-icon>
                            Воccтановить
                        </v-btn>
                        <v-btn :to="{ name: 'register' }">
                            <v-icon left>mdi-account-plus</v-icon>
                            Регистрация
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
    </v-container>


</template>

<script>
    import authMixin from "../mixins/authMixin";
    import moment from "moment";

    export default {
        name: "Login",
        mixins: [authMixin],
        data() {
            return {
                user: {
                    email: '',
                    password: '',
                },
                error: {},
                notForgot: true,
                loginLoading: false,
                forgotLoading: false,
            }
        },
        computed: {},
        methods: {
            login() {
                this.loginLoading = true;
                this.$store.dispatch('AUTH/LOGIN', this.user)
                    .then(() => {
                        this.user.login = '';
                        this.user.password = '';
                        this.$store.dispatch('EXCHANGE-RATE/SET', moment().format('Y-MM-DD'))
                        this.$router.back()
                    })
                    .catch((error) => {
                        this.error = error.response.data.errors;
                    })
                    .then(() => this.loginLoading = false);
            },
            forgot() {
                this.forgotLoading = true;
                this.$store.dispatch('AUTH/FORGOT', this.user)
                    .then(() => {
                        this.notForgot = false;
                    })
                    .catch((error) => {
                        this.error = error.response.data.errors;
                    })
                    .then(() => this.forgotLoading = false);
            }
        },
    }
</script>

<style scoped>

</style>
