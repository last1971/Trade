<template>
    <v-data-table
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
                        Всего {{ totalCount }} на сумму {{ totalAmount }}
                    </v-col>
                </v-row>
            </div>
        </template>
        <template v-slot:item.good.name.NAME="{ item }">
            <good-name v-model="item.good"/>
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

export default {
    name: "GoodsList",
    components: {BuyerSelect, GoodName},
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
                console.log(v);
                this.$store.commit('GOODS-LIST/BUYER-ID', v);
            }
        },
        totalCount() {
            //return this.$store.getters['GOODS-LIST/TOTAL-COUNT'];
        },
        totalAmount() {
            //return this.$store.getters['GOODS-LIST/TOTAL-AMOUNT'];
        }
    }
}
</script>

<style scoped>

</style>
