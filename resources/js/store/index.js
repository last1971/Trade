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
import goodsList from "./goodsList";
import retailOrderStatus from "./retailOrderStatus";
import retailSale from "./retailSale";
import retailSaleLine from "./retailSaleLine";
import retailStoreReturn from "./retailStoreReturn";
import exchangeRate from './exchangeRate';
import payment from "./payment";
import paymentOrder from "./paymentOrder";
import firmHistory from "./firmHistory";
import sellerPrice from "./sellerPrice";
import storeLine from "./storeLine";
import sellerOrder from "./sellerOrder";
import unitCode from "./unitCode";
import unitCodeAlias from "./unitCodeAlias";

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
        'EXCHANGE-RATE': exchangeRate,
        EMPLOYEE: employee,
        'ERROR-MESSAGE': errorMessage,
        FIRM: firm,
        'FIRM-HISTORY': firmHistory,
        INVOICE: invoice,
        'INVOICE-LINE': invoiceLine,
        INVOICESTATUS: invoiceStatus,
        GOOD: good,
        'GOODS-LIST': goodsList,
        NAME: name,
        'ORDER': order,
        'ORDER-IMPORT-LINE': orderImportLine,
        'ORDER-LINE': orderLine,
        'ORDER-STEP': orderStep,
        ORDERSTATUS: orderStatus,
        PAYMENT: payment,
        'PAYMENT-ORDER': paymentOrder,
        RESERVE: reserve,
        'RETAIL-PRICE': retailPrice,
        'RETAIL-ORDER-LINE': retailOrderLine,
        'RETAIL-ORDER-STATUS': retailOrderStatus,
        'RETAIL-SALE': retailSale,
        'RETAIL-SALE-LINE': retailSaleLine,
        'RETAIL-STORE-RETURN': retailStoreReturn,
        ROLE: role,
        SBIS: sbis,
        SELLER: seller,
        'SELLER-PRICE': sellerPrice,
        'SELLER-ORDER': sellerOrder,
        SNACKBAR: snackbar,
        'STORE-LINE': storeLine,
        'TRANSFER-OUT': transferOut,
        'TRANSFER-OUT-LINE': transferOutLine,
        USER: user,
        'UNIT-CODE': unitCode,
        'UNIT-CODE-ALIAS': unitCodeAlias,
    }
})
