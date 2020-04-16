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
import Users from "./components/Users";

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
        meta: {requiresAuth: true, model: 'INVOICE-LINE', permission: 'invoice.show'},
    },
    {
        name: 'invoices',
        path: '/invoice',
        component: Invoices,
        meta: {requiresAuth: true, model: 'INVOICE', permission: 'invoice.index'},
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
        meta: {requiresAuth: true, model: 'ORDER-LINE', permission: 'order.show'},
    },
    {
        name: 'orders',
        path: '/order',
        component: Orders,
        meta: {requiresAuth: true, model: 'ORDER', permission: 'order.index'},
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
        meta: {requiresAuth: true, model: 'TRANSFER-OUT-LINE', permission: 'transfer-out.show'},
    },
    {
        name: 'transfer-outs',
        path: '/transfer-out',
        component: TransferOuts,
        meta: {requiresAuth: true, model: 'TRANSFER-OUT', permission: 'transfer-out.index'},
    },
    {
        name: 'users',
        path: '/user',
        component: Users,
        meta: {requiresAuth: true, model: 'USER', permission: 'user.index'},
    },
    {path: '*', component: Help},
];

const router = new VueRouter({
    mode: 'history',
    routes
});

router.beforeEach((to, from, next) => {
    if (to.matched.some(record => record.meta.requiresAuth)) {
        if (store.getters['AUTH/IS_LOGGEDIN']) {
            if (!to.meta.permission || store.getters['AUTH/HAS_PERMISSION'](to.meta.permission)) next();
            else next({name: 'help'});
            return;
        }
        next({name: 'login'});
    } else {
        next();
    }
});

export default router
