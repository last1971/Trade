import Vue from 'vue';
import VueRouter from 'vue-router';
import store from './store/index';
import ExampleComponent from "./components/ExampleComponent";
import Help from "./components/Help";
import Login from "./components/Login";
import Register from "./components/Register";
import Invoice from "./components/Invoice";
import Invoices from "./components/Invoices";
import TransferOuts from "./components/TransferOuts";
import TransferOut from "./components/TransferOut";
import Orders from "./components/Orders";
import Order from "./components/Order";

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
        name: 'invoice',
        path: '/invoice/:id',
        component: Invoice,
        meta: {requiresAuth: true, model: 'INVOICE-LINE'},
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
        name: 'order',
        path: '/order/:id',
        component: Order,
        meta: {requiresAuth: true, model: 'ORDER-LINE'},
    },
    {
        name: 'orders',
        path: '/order',
        component: Orders,
        meta: {requiresAuth: true, model: 'ORDER'},
    },
    {
        name: 'register',
        path: '/register',
        component: Register,
    },
    {
        name: 'transfer-out',
        path: '/transfer-out/:id',
        component: TransferOut,
        meta: {requiresAuth: true, model: 'TRANSFER-OUT-LINE'},
    },
    {
        name: 'transfer-outs',
        path: '/transfer-out',
        component: TransferOuts,
        meta: {requiresAuth: true, model: 'TRANSFER-OUT'},
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
