<template>
    <v-data-table
        :footer-props="{
            showFirstLastPage: true,
        }"
        :headers="modifiedHeaders"
        :items="items"
        :loading="loading"
        :options.sync="options"
        item-key="id"
        :loading-text="loadingText"
        dense
    >
        <template v-slot:top >
            <v-card class="sticky">
                <v-card-text>
                    <v-row dense>
                        <v-col>
                            <v-text-field label="Строка поиска"
                                          placeholder="Введите название компонента"
                                          v-model="search"

                            />
                        </v-col>
                        <v-col cols="2">
                            <v-text-field label="Количество"
                                          placeholder="Введите количество"
                                          v-model="quantity"


                            />
                        </v-col>
                        <v-col v-if="hasPermission('seller-price.full')" cols="1">
                            <v-text-field label="Наценка"
                                          placeholder="Введите наценку"
                                          v-model="markup"
                                          append-icon="mdi-percent"
                            />
                        </v-col>
                        <v-col v-if="hasPermission('seller-price.full')">
                            <v-switch v-model="isAll" :label="isAllLabel"/>
                        </v-col>
                    </v-row>
                    <v-row v-if="hasPermission('seller-price.full')" dense>
                        <seller-api-file-select-new @seller-on="update" />
                    </v-row>
                </v-card-text>
            </v-card>
        </template>
        <template v-slot:item.seller="{ item }">
            <v-container>
            <v-row dense>
                <v-col>
                    <b>{{ sellerName(item.sellerId) }}</b>
                </v-col>
            </v-row>
            <v-row dense>
                <v-col class="text-caption">
                    {{ item.code }}
                </v-col>
            </v-row>
            <v-row dense>
                <v-col>
                    <v-icon v-if="item.isApi">mdi-api</v-icon>
                    <v-icon v-else>mdi-database</v-icon>
                    <v-icon v-if="item.isSomeoneElsesWarehouse">mdi-earth</v-icon>
                    <v-icon v-if="item.pos" color="orange">mdi-alarm-light</v-icon>
                </v-col>
            </v-row>
            </v-container>
        </template>
        <template v-slot:item.name="{ item }">
            <seller-price-name :item="item" />
        </template>
        <template v-slot:item.quantity="{ item }">
            <seller-price-quantity :item="item" :markup="markup"/>
        </template>
        <template v-slot:item.price="{ item }">
            <seller-price-prices :item="item" :markup="markup"/>
        </template>
        <template v-slot:item.amount="{ item }">
            <v-container>
                <v-row dense>
                    <v-col>
                        <b>
                            {{ toRub(item.CharCode, item.price * item.orderQuantity, item.sellerId) | formatRub }}
                            ({{ toUsd(item.CharCode, item.price * item.orderQuantity) | formatUsd }})
                        </b>
                    </v-col>
                </v-row>
                <v-row dense>
                    <v-col class="text-caption">
                        {{ toRub(item.CharCode, retailPrice(item) * item.orderQuantity, item.sellerId) | formatRub }}
                        ({{ toUsd(item.CharCode, retailPrice(item) * item.orderQuantity) | formatUsd }})
                    </v-col>
                </v-row>
                <v-row dense>
                    <v-col>
                        {{ toRub(item.CharCode, item.price * item.orderQuantity * (1 + markup / 100), item.sellerId)  | formatRub }}
                        ({{ toUsd(item.CharCode, item.price * item.orderQuantity * (1 + markup / 100)) | formatUsd }})
                    </v-col>
                </v-row>
            </v-container>
        </template>
        <template v-slot:item.deliveryTime="{ item }">
            <seller-price-delivery-time :item="item" @update="update(item)" />
        </template>
    </v-data-table>
</template>

<script>
import { mapGetters } from 'vuex';
import SellerApiFileSelect from "./SellerApiFileSelect.vue";
import GoodInString from "../good/GoodInString";
import GoodSelect from "../good/GoodSelect";
import SellerPriceName from "./SellerPriceName";
import SellerPriceQuantity from "./SellerPriceQuantity";
import SellerPricePrices from "./SellerPricePrices";
import SellerPriceDeliveryTime from "./SellerPriceDeliveryTime";
import moment from "moment";
import InvoiceCard from "../invoice/InvoiceCard";
import SellerApiFileSelectNew from "./SellerApi/SellerApiFileSelectNew";

export default {
    name: "SellerPrices",
    components: {
        SellerApiFileSelectNew,
        InvoiceCard,
        SellerPriceDeliveryTime,
        SellerPricePrices, SellerPriceQuantity, SellerPriceName, GoodSelect, GoodInString, SellerApiFileSelect},
    data() {
        return {
            loading: false,
            options: {},
            loadingText: 'Еще чуть-чуть ...',
            search: '',
            handlers: [],
            markup: 20,
            buyerHeaders: [
                'name', 'quantity', 'price', 'deliveryTime'
            ],
            goodPromise: Promise.resolve(),
        }
    },
    computed: {
        ...mapGetters({
            headers: 'SELLER-PRICE/HEADERS',
            items: 'SELLER-PRICE/FILTERD_DATA',
            retailPrice: 'SELLER-PRICE/RETAIL_PRICE',
            isInput: 'SELLER-PRICE/IS_INPUT',
            sellers: 'SELLER-PRICE/SELLERS',
            rate: 'EXCHANGE-RATE/GET',
            toRub: 'EXCHANGE-RATE/TO_RUB',
            toUsd: 'EXCHANGE-RATE/TO_USD',
            hasPermission: 'AUTH/HAS_PERMISSION',
        }),
        modifiedHeaders() {
            return this.headers.filter((header) =>
                this.hasPermission('seller-price.full') || this.buyerHeaders.indexOf(header.value) >= 0
            );
        },
        usd() {
            return this.rate('USD').value;
        },
        isAll: {
            get() {
                return this.$store.getters['SELLER-PRICE/IS_ALL'];
            },
            set(v) {
                this.$store.commit('SELLER-PRICE/SET_IS_ALL', v);
            }
        },
        quantity: {
            get() {
                return this.$store.getters['SELLER-PRICE/QUANTITY'];
            },
            set(v) {
                this.$store.commit('SELLER-PRICE/SET_QUANTITY', v);
            }
        },
        isAllLabel() {
            return this.isAll ? 'Все строки' : 'Строки с подходящим количеством';
        },
        total() {
            return this.items.length;
        }
    },
    watch: {
        search: _.debounce(async function () {
            if (this.$store.getters['EXCHANGE-RATE/DATE'] !== moment().format('Y-MM-DD')) {
                await this.$store.dispatch('EXCHANGE-RATE/SET', moment().format('Y-MM-DD'))
            }
            let { search } = this;
            if (!search || search.trim().length  < 3) return;
            try {
                this.$store.commit('SELLER-PRICE/CLEAR_DATA');
                this.$store.commit('SELLER-PRICE/CLEAR_ALL_API_ERRORS');
                const selectedSellerId = this.$store.getters['SELLER-PRICE/SELECTED_SELLER_ID'];
                this.$store.commit('SELLER-PRICE/SELLER_SELECT', selectedSellerId);
                await Promise.all(this.handlers);
                this.loading = true;
                const sellers = this.sellers
                    .filter((seller) => seller.isApi)
                    .reduce((map, seller) => {
                        if (seller.rate < 2000) map[0].push(seller);
                        if (seller.rate >= 2000 && seller.rate <= 5000) map[1].push(seller);
                        if (seller.rate > 5000) map[2].push(seller);
                        return map;
                    }, [[], [], []]);
                this.handlers = sellers.filter((seller) => !_.isEmpty(seller)).map((sellersArray) => {
                    sellersArray.forEach((seller) => seller.loading = true);
                    return this.$store.dispatch(
                        'SELLER-PRICE/GET',
                        {
                            sellerIds: sellersArray.map((seller) => seller.sellerId),
                            search
                        }
                    ).then(() => sellersArray.forEach((seller) => seller.loading = false))
                });



                //    this.sellers
                //    .filter((seller) => seller.isApi)
                    //.map((seller) => {
                    //    seller.loading = true;
                    //    return this.$store.dispatch('SELLER-PRICE/GET', {sellerId: [seller.sellerId], search})
                    //        .then(() => { seller.loading = false; }
                    //    )
                    //})
                await Promise.all(this.handlers);
            } catch (e) {
                console.log(e)
            } finally {
                this.loading = false;
            }
        }, 2000),
        async items(items) {
            await this.goodPromise;
            const goodIds = _.filter(
                _.uniqBy(_.filter(items, 'goodId'), 'goodId').map((item) => item.goodId),
                (id) => !this.$store.getters['GOOD/GET'](id)
            );
            if (goodIds.length > 0 && this.hasPermission('seller-price.full')) {
                this.goodPromise = this.$store.dispatch('GOOD/ALL', {

                        with: [
                            'retailPrice', 'orderStep', 'retailStore', 'warehouse', 'name', 'category', 'goodNames'
                        ],
                        aggregateAttributes: [
                            'reservesQuantity',
                            'invoiceLinesQuantity',
                            'invoiceLinesQuantityTransit',
                            'reservesQuantityTransit',
                            'pickUpsTransitQuantity',
                            'retailOrderLinesNeedQuantity',
                            'orderLinesTransitQuantity',
                            'shopLinesTransitQuantity',
                            'storeLinesQuantity',
                            'storeLinesTransitQuantity',
                            'transferOutLinesQuantity',
                        ],
                        filterAttributes: [
                            'GOODSCODE',
                        ],
                        filterOperators: ['IN', ],
                        filterValues: [goodIds],
                        itemsPerPage: -1,

                });
            }
        }
    },
    async created() {
        await this.$store.dispatch('SELLER-PRICE/GET_SELLERS');
    },
    methods: {
        async update(item, isUpdate = true) {
            const { sellerId } = item;
            const seller = this.seller(sellerId);
            const { search } = this;
            if (!seller.isApi || !search || search.trim().length  < 3) return;
            this.$store.commit('SELLER-PRICE/CLEAR_SELLER_DATA', sellerId);
            this.$store.commit('SELLER-PRICE/CLEAR_SELLER_API_ERROR', sellerId);
            this.loading = true;
            try {
                seller.loading = true;
                const handler = this.$store.dispatch(
                    'SELLER-PRICE/GET', { sellerId, search, isUpdate }
                ).then(() => { seller.loading = false; });
                this.handlers.push(handler);
                await Promise.all(this.handlers);
            } catch (e) {
                console.log(e);
            } finally {
                this.loading = false;
            }
        },
        seller(sellerId) {
            return _.find(this.sellers, { sellerId })
        },
        sellerName(sellerId) {
            return this.seller(sellerId).name;
        },
    }
}
</script>

<style scoped>
.sticky {
    position: sticky;
    top: var(--toolbarHeight);
    z-index: 2;
}
</style>
