import Vue from 'vue';
import Vuex from 'vuex';
import auth from './auth';
import snackbar from "./snackbar";
import buyer from "./buyer";
import invoice from "./invoice";
import invoiceStatus from "./invoiceStatus";
import invoiceLine from "./invoiceLine";
import transferOutLine from "./transferOutLine";
import transferOut from "./transferOut";
import firm from "./firm";
import breadcrumbs from "./breadcrumbs";
import orderLine from "./orderLine";
import order from "./order";
import orderStatus from "./orderStatus";
import role from "./role";
import seller from "./seller";
import user from "./user";
import employee from "./employee";
import category from "./category";

Vue.use(Vuex);

export default new Vuex.Store({
    state: {},
    getters: {},
    mutations: {},
    actions: {},
    modules: {
        AUTH: auth,
        BREADCRUMBS: breadcrumbs,
        BUYER: buyer,
        CATEGORY: category,
        EMPLOYEE: employee,
        FIRM: firm,
        INVOICE: invoice,
        'INVOICE-LINE': invoiceLine,
        INVOICESTATUS: invoiceStatus,
        'ORDER': order,
        'ORDER-LINE': orderLine,
        ORDERSTATUS: orderStatus,
        ROLE: role,
        SELLER: seller,
        SNACKBAR: snackbar,
        'TRANSFER-OUT': transferOut,
        'TRANSFER-OUT-LINE': transferOutLine,
        USER: user,
    }
})
