import Vue from 'vue';
import VueRouter from 'vue-router';
import store from './store/index';
import ExampleComponent from "./components/ExampleComponent";
import Help from "./components/Help";
import Login from "./components/Login";
import Register from "./components/Register";
import Invoices from "./components/Invoices";

Vue.use(VueRouter);

const routes = [
    {
        name: 'help',
        path: '/help',
        component: Help,
    },
    {
        name: 'home',
        path: '/',
        component: ExampleComponent,
        meta: {requiresAuth: true},
    },
    {
        name: 'invoices',
        path: '/invoice',
        component: Invoices,
        meta: {requiresAuth: true, model: 'INVOICE'},
    },
    {
        name: 'login',
        path: '/login',
        component: Login,
    },
    {
        name: 'register',
        path: '/register',
        component: Register,
    },
    {path: '*', component: Help},
];

const router = new VueRouter({
    mode: 'history',
    routes
});

router.beforeEach((to, from, next) => {
    if (to.matched.some(record => record.meta.requiresAuth)) {
        if (store.getters['USER/IS_LOGGEDIN']) {
            if (store.getters['USER/IS_GUEST']) {
                next({name: 'help'});
            } else {
                next();
            }
            return;
        }
        next({name: 'login'});
    } else {
        next();
    }
});

export default router
