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
                        <v-toolbar-title>Регистрация</v-toolbar-title>
                    </v-toolbar>
                    <v-card-text class="px-4">
                        <v-form>
                            <v-text-field
                                :error-messages="error.name"
                                label="Имя"
                                prepend-icon="mdi-account"
                                type="text"
                                v-model="user.name"
                            />
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
                    </v-card-text>
                    <v-card-actions class="px-4 pb-4">
                        <v-spacer/>
                        <v-btn @click="register" color="primary">Зарегистрироваться</v-btn>
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
        name: "Register",
        mixins: [authMixin],
        data() {
            return {
                user: {
                    name: '',
                    email: '',
                    password: '',
                    password_confirmation: '',
                },
                error: {},
            }
        },
        methods: {
            register() {
                this.$store.dispatch('AUTH/REGISTER', this.user)
                    .then(() => {
                        this.$store.dispatch('EXCHANGE-RATE/SET', moment().format('Y-MM-DD'))
                        this.$store.dispatch('CONFIG/LOAD')
                        this.$router.push({name: 'help'})
                    })
                    .catch((error) => {
                        this.error = error.response.data.errors;
                    });
            }
        }
    }
</script>

<style scoped>

</style>
