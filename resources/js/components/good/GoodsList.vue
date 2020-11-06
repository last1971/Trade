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
                        <buyer-select v-model="buyerId" :disabled="!opened"/>
                    </v-col>
                   <v-col>
                        <v-switch
                            v-model="isRetailStore"
                            :label="isRetailStore ? 'Есть в магазине' : 'Все в магазине'"
                            :disabled="!opened"
                        ></v-switch>
                    </v-col>
                    <v-col>
                        Всего {{ totalCount }} позиций на сумму {{ totalAmount | formatRub }}
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
        }
    }
}
</script>

<style scoped>

</style>
