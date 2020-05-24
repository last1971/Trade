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
import advancedBuyer from "./advancedBuyer";
import errorMessage from "./errorMessage";
import sbis from "./sbis";
import good from "./good";
import name from "./name";
import retailPrice from "./retailPrice";
import orderStep from "./orderStep";
import reserve from "./reserve";
import retailOrderLine from "./retailOrderLine";
import orderImportLine from "./orderImportLine";

Vue.use(Vuex);

export default new Vuex.Store({
    state: {},
    getters: {
        VAT: (date) => {
            if (new Date(date) < new Date('2019-01-01')) return 18;
            return 20;
        }
    },
    mutations: {},
    actions: {},
    modules: {
        'ADVANCED-BUYER': advancedBuyer,
        AUTH: auth,
        BREADCRUMBS: breadcrumbs,
        BUYER: buyer,
        CATEGORY: category,
        EMPLOYEE: employee,
        'ERROR-MESSAGE': errorMessage,
        FIRM: firm,
        INVOICE: invoice,
        'INVOICE-LINE': invoiceLine,
        INVOICESTATUS: invoiceStatus,
        GOOD: good,
        NAME: name,
        'ORDER': order,
        'ORDER-IMPORT-LINE': orderImportLine,
        'ORDER-LINE': orderLine,
        'ORDER-STEP': orderStep,
        ORDERSTATUS: orderStatus,
        RESERVE: reserve,
        'RETAIL-PRICE': retailPrice,
        'RETAIL-ORDER-LINE': retailOrderLine,
        ROLE: role,
        SBIS: sbis,
        SELLER: seller,
        SNACKBAR: snackbar,
        'TRANSFER-OUT': transferOut,
        'TRANSFER-OUT-LINE': transferOutLine,
        USER: user,
    }
})
