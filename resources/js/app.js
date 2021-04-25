/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import moment from 'moment';
import vuetify from "./vuetify";
import router from "./router";
import store from "./store/index";

require('./bootstrap');
require('moment/locale/ru');

window.Vue = require('vue');

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i)
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))

Vue.component('app', require('./components/App.vue').default);

Vue.filter('formatDate', function (d) {
    return moment(d).format('DD.MM.Y');
});
Vue.filter('formatDateTime', function (d) {
    return moment(d).format('DD.MM.Y H:mm:ss');
});
Vue.filter('formatRub', function (d) {
    return new Intl.NumberFormat('ru-RU', {style: 'currency', currency: 'RUB'}).format(d);
});
Vue.filter('formatUsd', function (d) {
    return new Intl.NumberFormat(
        'en-EN', {style: 'currency', currency: 'USD', minimumFractionDigits : 4 }
    ).format(d);
});
Vue.filter('formatPercent', function (d) {
    return new Intl.NumberFormat('ru-RU', { style: 'percent', minimumFractionDigits: 2 }).format(d);
});

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
    el: '#app',
    vuetify,
    router,
    store,
    created() {
        if (document.head.querySelector('meta[name="token"]')) {
            localStorage.setItem('token', document.head.querySelector('meta[name="token"]').content)
        }
        if (this.$store.getters['AUTH/IS_LOGGEDIN']) {
            window.axios.defaults.headers.common['Authorization'] = 'Bearer ' + localStorage.getItem('token');
            this.$store.dispatch('EXCHANGE-RATE/SET', moment().format('Y-MM-DD'))
            this.$store.dispatch('AUTH/REFRESH')
                .then(() => {
                    if (this.$store.getters['AUTH/IS_GUEST']) this.$router.push({name: 'help'});
                })
                .catch(() => {
                    this.$router.push({name: 'login'});
                });
        }
    }
});
