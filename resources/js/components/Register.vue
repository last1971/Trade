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
    export default {
        name: "Register",
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
        computed: {},
        methods: {
            register() {
                this.$store.dispatch('USER/REGISTER', this.user)
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
