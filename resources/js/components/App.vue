<template>
    <v-app>
        <v-navigation-drawer
            app
            v-if="user && hasPermission('nav')"
            v-model="drawer"
            temporary
            expand-on-hover
        >
            <v-list dense nav>
                <!-- Пункты вне групп (Домой) — они одиночные, прятать их за раскрытие незачем -->
                <v-list-item
                    :key="item.id"
                    :to="item.to"
                    link
                    v-for="item in rootItems"
                >
                    <v-list-item-action>
                        <v-icon>{{ item.icon }}</v-icon>
                    </v-list-item-action>
                    <v-list-item-content>
                        <v-list-item-title>{{ item.text }}</v-list-item-title>
                    </v-list-item-content>
                </v-list-item>

                <!-- Группа с текущей страницей раскрыта сразу: человек видит, где он находится -->
                <v-list-group
                    :key="group.text"
                    :prepend-icon="group.icon"
                    :value="group.active"
                    no-action
                    v-for="group in menuGroups"
                >
                    <template v-slot:activator>
                        <v-list-item-content>
                            <v-list-item-title>{{ group.text }}</v-list-item-title>
                        </v-list-item-content>
                    </template>
                    <v-list-item
                        :key="item.id"
                        :to="item.to"
                        link
                        v-for="item in group.items"
                    >
                        <v-list-item-action>
                            <v-icon>{{ item.icon }}</v-icon>
                        </v-list-item-action>
                        <v-list-item-content>
                            <v-list-item-title>{{ item.text }}</v-list-item-title>
                        </v-list-item-content>
                    </v-list-item>
                </v-list-group>
            </v-list>
        </v-navigation-drawer>

        <v-app-bar
            app
            color="primary"
            dark
            ref="toolbar" v-mutate="onMutate"
        >
            <v-app-bar-nav-icon @click.stop="drawer = !drawer"/>
            <v-toolbar-title>
                <v-breadcrumbs :items="breadcrumbs" large>
                    <template v-slot:item="{ item }">
                        <v-breadcrumbs-item :disabled="item.disabled">
                            <span v-if="item.disabled" class="white--text">{{ item.text }}</span>
                            <router-link v-else :to="item.to" class="white--text">
                                {{ item.text }}
                            </router-link>
                            <v-icon small color="white" class="ml-1" :title="item.icon.title"
                                    v-if="item.icon && $store.getters[item.icon.getter](item.icon.id)"
                            >{{ item.icon.name }}</v-icon>
                        </v-breadcrumbs-item>
                    </template>
                </v-breadcrumbs>
            </v-toolbar-title>
            <v-spacer></v-spacer>
            <v-tooltip bottom v-if="user && $route.name !== 'exchange-rates'">
                <template v-slot:activator="{ on }">
                    <v-btn @click="$router.push({ name: 'exchange-rates' })" icon v-on="on">
                        <v-icon>mdi-currency-usd</v-icon>
                    </v-btn>
                </template>
                <span>
                    На {{ exchangeDate | formatDate }} $ = {{ exchangeRate('USD').value | formatRub }},
                    € = {{ exchangeRate('EUR').value | formatRub }}
                </span>
            </v-tooltip>
            <v-tooltip bottom v-if="user">
                <template v-slot:activator="{ on }">
                    <v-btn @click="$router.back()" icon v-on="on">
                        <v-icon>mdi-arrow-left-circle</v-icon>
                    </v-btn>
                </template>
                <span>Вернуться</span>
            </v-tooltip>
            <goods-list-button v-if="user && !$vuetify.breakpoint.xsOnly && hasPermission('nav.goods-list')"
                               class="mr-2"
            />
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

        <v-main>
            <transition>
                <keep-alive>
                    <router-view></router-view>
                </keep-alive>
            </transition>
        </v-main>
        <v-footer
            app
            color="primary"
        >
            <span class="white--text">ООО "ЭлкоПро" &copy; 2020-2022</span>
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
    import GoodsListButton from "./good/GoodsListButton";

    // Магазинная инсталляция отличается только набором пунктов, поэтому список один,
    // а разница помечена полем where: 'both' | 'opt' | 'shop'. Двух списков быть не должно —
    // они разъезжаются молча, и пункт, добавленный в один, годами отсутствует в другом.
    const IS_SHOP = process.env.MIX_IS_ELECTRONICA === 'true';

    // Пункт вне групп: одиночный, прятать его за раскрытие незачем.
    const ROOT_ITEMS = [
        {id: 1, text: 'Домой', to: {name: 'home'}, icon: 'mdi-home', where: 'opt'},
    ];

    // Порядок групп — порядок работы: продали, отгрузили со склада, отчитались в ЧЗ.
    // id пунктов сохранены прежними: по ним ничего не ищется, но история читается легче.
    const GROUPS = [
        {
            text: 'Продажи',
            icon: 'mdi-cash-multiple',
            items: [
                {id: 4, text: 'Счета', to: {name: 'invoices'}, icon: 'mdi-text-box', where: 'both'},
                {id: 5, text: 'Поиск в счетах', to: {name: 'invoice-lines'}, icon: 'mdi-format-line-spacing', where: 'opt'},
                {
                    id: 2,
                    text: 'Заказы розницы',
                    to: {name: 'retail-order-lines'},
                    icon: 'mdi-order-alphabetical-ascending',
                    where: 'shop',
                },
                {id: 12, text: 'Розн.продажи', to: {name: 'retail-sales'}, icon: 'mdi-store-24-hour', where: 'shop'},
                {id: 6, text: 'Исх.УПД', to: {name: 'transfer-outs'}, icon: 'mdi-clipboard-text-play', where: 'both'},
                {id: 16, text: 'Долги и отгрузки', to: {name: 'buyer-debt'}, icon: 'mdi-cash-register', where: 'opt'},
                {id: 9, text: 'Покупатели+', to: {name: 'advanced-buyer'}, icon: 'mdi-account-plus', where: 'opt'},
                {id: 13, text: 'Платежи', to: {name: 'payments'}, icon: 'mdi-credit-card-settings-outline', where: 'shop'},
                {id: 8, text: 'СБИС', to: {name: 'sbis'}, icon: 'mdi-electron-framework', where: 'opt'},
            ],
        },
        {
            text: 'Склад и закупка',
            icon: 'mdi-package-variant-closed',
            items: [
                {id: 21, text: 'Приходы', to: {name: 'store-ins'}, icon: 'mdi-truck-delivery', where: 'both'},
                {id: 7, text: 'Заказы', to: {name: 'orders'}, icon: 'mdi-clipboard-arrow-left', where: 'both'},
                {id: 17, text: 'Закупка', to: {name: 'replenish'}, icon: 'mdi-cart-arrow-down', where: 'both'},
                {id: 22, text: 'Списания', to: {name: 'spis-sklads'}, icon: 'mdi-delete-sweep', where: 'both'},
                {id: 19, text: 'Разгребание склада', to: {name: 'stock-classif'}, icon: 'mdi-warehouse', where: 'both'},
            ],
        },
        {
            text: 'Маркировка',
            icon: 'mdi-qrcode-scan',
            items: [
                {id: 20, text: 'Марки ЧЗ', to: {name: 'mark-codes'}, icon: 'mdi-qrcode', where: 'both'},
                {id: 23, text: 'Отправка в ЧЗ', to: {name: 'chz-outbox'}, icon: 'mdi-cloud-upload', where: 'both'},
            ],
        },
        {
            text: 'Справочники',
            icon: 'mdi-book-open-variant',
            items: [
                {id: 3, text: 'Товары', to: {name: 'goods'}, icon: 'mdi-chip', where: 'both'},
                {id: 11, text: 'Список', to: {name: 'goods-list'}, icon: 'mdi-playlist-edit', where: 'shop'},
                {id: 15, text: 'Ед.изм.', to: {name: 'unit-code'}, icon: 'mdi-numeric-9-plus', where: 'opt'},
                {id: 18, text: 'Сертификаты', to: {name: 'certificates'}, icon: 'mdi-certificate', where: 'both'},
            ],
        },
        {
            text: 'Настройки',
            icon: 'mdi-cog',
            items: [
                {id: 10, text: 'Пользователи', to: {name: 'users'}, icon: 'mdi-account-multiple', where: 'both'},
                {id: 14, text: 'Test', to: {name: 'test'}, icon: 'mdi-test-tube', where: 'both'},
            ],
        },
    ];

    /** Пункт этой инсталляции: 'both' — обеим, иначе только своей. */
    const forInstall = (item) => item.where === 'both' || item.where === (IS_SHOP ? 'shop' : 'opt');

    export default {
        name: "App",
        components: {GoodsListButton},
        props: {
            source: String,
        },
        data: () => ({
            drawer: null,
        }),
        computed: {
            ...mapGetters({
                user: 'AUTH/GET',
                hasPermission: 'AUTH/HAS_PERMISSION',
                snackbar: 'SNACKBAR/GET',
                exchangeDate: 'EXCHANGE-RATE/DATE',
                exchangeRate: 'EXCHANGE-RATE/GET',
            }),
            rootItems() {
                return ROOT_ITEMS.filter((item) => forInstall(item) && this.hasPermission('nav.' + item.to.name));
            },
            /**
             * Группы для этой инсталляции и этого пользователя. Группа без доступных
             * пунктов не показывается вовсе — пустая строка меню хуже отсутствующей.
             */
            menuGroups() {
                return GROUPS
                    .map((group) => {
                        const items = group.items
                            .filter((item) => forInstall(item) && this.hasPermission('nav.' + item.to.name));
                        return {...group, items, active: items.some((item) => item.to.name === this.$route.name)};
                    })
                    .filter((group) => group.items.length > 0);
            },
            breadcrumbs() {
                // disabled вычисляется по позиции: кликабельны все, кроме текущей.
                const items = this.$store.getters['BREADCRUMBS/ALL']
                    .map((item, index, all) => ({...item, disabled: index === all.length - 1}));
                if (this.$vuetify.breakpoint.xsOnly) {
                    return !_.isEmpty(items) ? [_.last(items)] : [];
                }
                return items;
            }
        },
        watch: {
            // Уходя со страницы, фиксируем её точный адрес (с query) в её крошке —
            // возврат по крошке ведёт туда, откуда пришли, с фильтрами и страницей.
            $route(to, from) {
                if (from && from.name) {
                    this.$store.commit('BREADCRUMBS/SYNC', {
                        name: from.name,
                        params: from.params,
                        query: from.query,
                    });
                }
            },
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
            },
            onMutate () {
                let height = 0
                const toolbar = this.$refs.toolbar
                if (toolbar) {
                    height = `${toolbar.$el.offsetHeight}px`
                }
                document.documentElement.style.setProperty('--toolbarHeight', height)
            }
        },
        mounted() {
        },
    }
</script>

<style scoped>

</style>
