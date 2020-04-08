import Vue from 'vue';
import Vuex from 'vuex';
import user from './user';
import snackbar from "./snackbar";
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
        INVOICE: invoice,
        'INVOICE-LINE': invoiceLine,
        INVOICESTATUS: invoiceStatus,
        SNACKBAR: snackbar,
        'TRANSFER-OUT': transferOut,
        'TRANSFER-OUT-LINE': transferOutLine,
        USER: user,
    }
})
