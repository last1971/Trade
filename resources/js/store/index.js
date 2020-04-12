import Vue from 'vue';
import Vuex from 'vuex';
import user from './user';
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

Vue.use(Vuex);

export default new Vuex.Store({
    state: {},
    getters: {},
    mutations: {},
    actions: {},
    modules: {
        BREADCRUMBS: breadcrumbs,
        BUYER: buyer,
        FIRM: firm,
        INVOICE: invoice,
        'INVOICE-LINE': invoiceLine,
        INVOICESTATUS: invoiceStatus,
        'ORDER-LINE': orderLine,
        SNACKBAR: snackbar,
        'TRANSFER-OUT': transferOut,
        'TRANSFER-OUT-LINE': transferOutLine,
        USER: user,
    }
})
