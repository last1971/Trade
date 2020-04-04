import Vue from 'vue';
import Vuex from 'vuex';
import user from './user';
import snackbar from "./snackbar";
import invoice from "./invoice";
import invoiceStatus from "./invoiceStatus";

Vue.use(Vuex);

export default new Vuex.Store({
    state: {},
    getters: {},
    mutations: {},
    actions: {},
    modules: {
        INVOICE: invoice,
        INVOICESTATUS: invoiceStatus,
        SNACKBAR: snackbar,
        USER: user,
    }
})
