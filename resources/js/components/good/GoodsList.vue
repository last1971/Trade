<template>
    <v-data-table
        :key="renderKey"
        :headers="headers"
        :items="items"
        item-key="GODDSCODE"
        :disable-pagination="true"
        :hide-default-footer="true"
        :items-per-page="-1"
    >
        <template v-slot:top>
            <div class="m-2">
                <v-row>
                    <v-col>
                        <v-switch
                            v-model="opened"
                            :label="opened ? 'Стереть' : 'Открыть'"
                        ></v-switch>
                    </v-col>
                    <v-col>
                        <buyer-select v-model="buyerId" :disabled="!opened || isRetailOrder"/>
                    </v-col>
                   <v-col>
                        <v-switch
                            v-model="isRetailStore"
                            :label="isRetailStore ? 'Есть в магазине' : 'Все в магазине'"
                            :disabled="!opened"
                        ></v-switch>
                    </v-col>
                    <v-col>
                        <div class="text-subtitle-1">
                            Всего {{ totalCount }} позиций на сумму {{ totalAmount | formatRub }}
                        </div>
                    </v-col>
                    <v-col>
                        <v-speed-dial :open-on-hover="false" direction="bottom" >
                            <template v-slot:activator>
                                <v-btn
                                       rounded
                                       color="primary"
                                       class="mt-3"
                                       :disabled="!opened || !isRetailStore || isEmpty"
                                       :loading="loading"
                                >
                                    SALE
                                    <v-icon class="ml-2">mdi-printer-pos</v-icon>
                                </v-btn>
                            </template>
                            <template v-slot:default v-if="!(!opened || !isRetailStore || isEmpty)">
                            <v-btn rounded color="success" @click="sale('cash')">
                                CASH
                                <v-icon class="ml-2">mdi-cash</v-icon>
                            </v-btn>
                            <v-btn rounded color="error" @click="sale('electronically')">
                                CARD
                                <v-icon class="ml-2">mdi-credit-card</v-icon>
                            </v-btn>
                            <v-btn rounded color="secondary" @click="sale('black')">
                                BLACK
                                <v-icon class="ml-2">mdi-account-cash-outline</v-icon>
                            </v-btn>
                            </template>
                        </v-speed-dial>
                    </v-col>
                </v-row>
            </div>
        </template>
        <template v-slot:item.actions="{ item }">
            <v-hover v-slot="{ hover}">
                <v-btn icon color="red" @click="remove(item)">
                    <v-icon v-if="hover">mdi-cart-remove</v-icon>
                </v-btn>
            </v-hover>
        </template>
        <template v-slot:item.good.name.NAME="{ item }">
            <good-name v-model="item.good"/>
        </template>
        <template v-slot:item.quantity="{ item }">
            <edit-field
                        :rules="[rules.isInteger, rules.required, rules.positive, rules.upperLimit(retailStore(item))]"
                        @save="save"
                        attribute="quantity"
                        v-model="item"
            />
        </template>
        <template v-slot:item.price="{ item }">
            {{ item.price | formatRub }}
        </template>
        <template v-slot:item.discount="{ item }">
            {{ item.discount / 100 | formatPercent }}
        </template>
        <template v-slot:item.amount="{ item }">
            {{ item.amount | formatRub }}
        </template>
    </v-data-table>
</template>

<script>
import GoodName from "./GoodName";
import BuyerSelect from "../BuyerSelect";
import EditField from "../EditField";
import utilsMixin from "../../mixins/utilsMixin";

export default {
    name: "GoodsList",
    components: {BuyerSelect, GoodName, EditField},
    mixins: [utilsMixin],
    data() {
        return {
            loading: false
        }
    },
    computed: {
        headers() {
            return this.$store.getters['GOODS-LIST/HEADERS'];
        },
        items() {
            return this.$store.getters['GOODS-LIST/ALL'];
        },
        opened: {
            get() {
                return this.$store.getters['GOODS-LIST/OPENED'];
            },
            set(v) {
                this.$store.commit('GOODS-LIST/OPENED', v);
            }
        },
        isRetailStore: {
            get() {
                return this.$store.getters['GOODS-LIST/IS-RETAIL-STORE'];
            },
            set(v) {
                this.$store.commit('GOODS-LIST/IS-RETAIL-STORE', v);
            }
        },
        buyerId: {
            get() {
                return this.$store.getters['GOODS-LIST/BUYER-ID'];
            },
            set(v) {
                this.$store.commit('GOODS-LIST/BUYER-ID', v);
                this.$store.commit('GOODS-LIST/APPLAY-DISCOUNT');
            }
        },
        totalCount() {
            return this.$store.getters['GOODS-LIST/TOTAL-COUNT'];
        },
        totalAmount() {
            return this.$store.getters['GOODS-LIST/TOTAL-AMOUNT'];
        },
        renderKey() {
            return this.$store.getters['GOODS-LIST/RENDER-KEY'];
        },
        isRetailOrder() {
            return !!this.items.reduce((a, v) => a || v.retailOrderLineId, false);
        },
        isEmpty() {
            return this.items.length === 0;
        }
    },
    methods: {
        retailStore(item) {
            return item.good.retailStore ? item.good.retailStore.QUAN : 0;
        },
        save(item) {
            this.$store.commit('GOODS-LIST/CHANGE-QUANTITY', item);
        },
        remove(item) {
            this.$store.commit('GOODS-LIST/REMOVE', item)
        },
        sale(paymentType) {
            this.loading=true;
            this.$store.dispatch('GOODS-LIST/SALE', paymentType)
                .then(() => {
                    this.opened = false;
                    this.$router.push({ name: 'goods' });
                    this.opened = true;
                })
                .catch(e => {
                    this.$store.commit('SNACKBAR/ERROR', e.response.data.message);
                })
                .then(() => this.loading=false)
        }
    }
}
</script>

<style scoped>

</style>
