import Vue from 'vue';
import VueRouter from 'vue-router';
import store from './store/index';
import ExampleComponent from "./components/ExampleComponent";
import Help from "./components/Help";
import Login from "./components/Login";
import Register from "./components/Register";
import Invoice from "./components/invoice/Invoice";
import Invoices from "./components/invoice/Invoices";
import TransferOuts from "./components/transferOut/TransferOuts";
import TransferOut from "./components/transferOut/TransferOut";
import Orders from "./components/order/Orders";
import Order from "./components/order/Order";
import Users from "./components/Users";
import ResetPassword from "./components/ResetPassword";
import InvoiceLinesSearch from "./components/invoice/InvoiceLinesSearch";
import AdvancedBuyers from "./components/AdvancedBuyers";
import SbisComponent from "./components/SbisComponent";
import Goods from "./components/good/Goods";
import Good from "./components/good/Good";
import Test from "./components/Test";
import GoodsList from "./components/good/GoodsList";
import RetailOrderLines from "./components/Retail/RetailOrderLines";
import RetailSalesAndRefunds from "./components/Retail/RetailSalesAndRefunds";

Vue.use(VueRouter);

const routes = [
    {
        name: 'advanced-buyer',
        path: '/advanced-buyer',
        component: AdvancedBuyers,
        meta: {requiresAuth: true, permission: 'advanced-buyer.index'},
    },
    {
        name: 'help',
        path: '/help',
        component: Help,
    },
    {
        name: 'good',
        path: '/good/:id',
        component: Good,
        meta: {requiresAuth: true, permission: 'good.show'},
    },
    {
        name: 'goods',
        path: '/good',
        component: Goods,
        meta: {requiresAuth: true, model: 'GOOD', permission: 'good.index'},
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
        name: 'invoice-lines',
        path: '/invoice-line',
        component: InvoiceLinesSearch,
        meta: {requiresAuth: true, model: 'INVOICE-LINE', permission: 'invoice-line.index'},
    },
    {
        name: 'login',
        path: '/login',
        component: Login,
    },
    {
        name: 'goods-list',
        path: '/goods-list',
        component: GoodsList,
        meta: {requiresAuth: true, permission: 'goods-list.show'},
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
        name: 'passwordReset',
        path: '/password-reset/:token',
        component: ResetPassword,
    },
    {
        name: 'register',
        path: '/register',
        component: Register,
    },
    {
        name: 'retail-order-lines',
        path: '/retail-order-lines',
        component: RetailOrderLines,
        meta: {requiresAuth: true, model: 'RETAIL-ORDER-LINE', permission: 'retail-order-line.show'},
    },
    {
        name: 'retail-sales',
        path: '/retail-sales',
        component: RetailSalesAndRefunds,
        meta: {requiresAuth: true, model: 'RETAIL-SALE', permission: 'retail-sale.show'},
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
    {
        name: 'sbis',
        path: '/sbis',
        component: SbisComponent,
        meta: {requiresAuth: true, permission: 'sbis.show'},
    },
    {
        name: 'test',
        path: '/test',
        component: Test,
        meta: {requiresAuth: true, model: 'GOOD', permission: 'user.index'},
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
