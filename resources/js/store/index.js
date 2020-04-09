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

Vue.use(Vuex);

export default new Vuex.Store({
    state: {},
    getters: {},
    mutations: {},
    actions: {},
    modules: {
        BUYER: buyer,
        INVOICE: invoice,
        'INVOICE-LINE': invoiceLine,
        INVOICESTATUS: invoiceStatus,
        SNACKBAR: snackbar,
        'TRANSFER-OUT': transferOut,
        'TRANSFER-OUT-LINE': transferOutLine,
        USER: user,
    }
})
