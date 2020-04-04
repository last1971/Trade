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
                        <v-form @submit="login">
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
                        </v-form>
                    </v-card-text>
                    <v-card-actions class="px-4 pb-4">
                        <v-spacer/>
                        <v-btn @click="login" color="primary" type="submit">Войти</v-btn>
                        <v-btn :to="{ name: 'register' }">Регистрация</v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
    </v-container>


</template>

<script>
    export default {
        name: "Login",
        data() {
            return {
                user: {
                    email: '',
                    password: '',
                },
                error: {},
            }
        },
        computed: {},
        methods: {
            login() {
                this.$store.dispatch('USER/LOGIN', this.user)
                    .then(() => this.$router.back())
                    .catch((error) => {
                        this.error = error.response.data.errors;
                    });
            }
        },
    }
</script>

<style scoped>

</style>
