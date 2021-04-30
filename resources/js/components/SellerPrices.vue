<template>
    <v-data-table
        :footer-props="{
            showFirstLastPage: true,
        }"
        :headers="headers"
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
                        <v-col cols="1">
                            <v-text-field label="Наценка"
                                          placeholder="Введите наценку"
                                          v-model="markup"
                                          append-icon="mdi-percent"
                            />
                        </v-col>
                        <v-col>
                            <v-switch v-model="isAll" :label="isAllLabel"/>
                        </v-col>
                    </v-row>
                    <v-row dense>
                        <v-slide-group
                            multiple
                            show-arrows
                        >
                            <v-slide-item v-for="seller in sellers"
                                          :key="seller.sellerId"
                            >
                                <seller-api-file-select :seller="seller" @seller-on="update"/>
                            </v-slide-item>
                        </v-slide-group>
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
                </v-col>
            </v-row>
            </v-container>
        </template>
        <template v-slot:item.name="{ item }">
            <v-container>
                <v-row dense>
                    <v-col>
                        <b>{{ showName(item) }}</b>
                    </v-col>
                </v-row>
                <v-row dense>
                    <v-col class="text-caption">
                        {{ showRemark(item) }}
                    </v-col>
                </v-row>
                <v-row dense>
                    <v-col>
                        <good-in-string v-if="good(item.goodId)" :value="good(item.goodId)" />
                        <div v-else>А Н Е Т У</div>
                    </v-col>
                </v-row>
            </v-container>
        </template>
        <template v-slot:item.quantity="{ item }">
            <v-container>
                <v-row dense>
                    <v-col>
                        Есть: <b>{{ item.quantity }}</b> / Берём:
                        <v-btn icon>
                            {{ item.orderQuantity }}
                        </v-btn>
                    </v-col>
                </v-row>
                <v-row dense>
                    <v-col class="text-caption">
                        Упак: {{ item.packageQuantity }} / Кратно: {{ item.multiplicity }}
                    </v-col>
                </v-row>
                <v-row dense>
                    <v-col>
                        Min: {{ item.minQuantity }} / Max: {{ item.maxQuantity }}
                    </v-col>
                </v-row>
            </v-container>
        </template>
        <template v-slot:item.price="{ item }">
            <v-container>
                <v-row dense>
                    <v-col>
                        <b>
                            {{ toRub(item.CharCode, item.price) | formatRub }}
                            ({{ toUsd(item.CharCode, item.price) | formatUsd }})
                        </b>
                    </v-col>
                </v-row>
                <v-row dense>
                    <v-col class="text-caption">
                        {{ toRub(item.CharCode, retailPrice(item)) | formatRub }}
                        ({{ toUsd(item.CharCode, retailPrice(item)) | formatUsd }})
                    </v-col>
                </v-row>
                <v-row dense>
                    <v-col>
                        {{ toRub(item.CharCode, item.price * (1 + markup / 100)) | formatRub }}
                        ({{ toUsd(item.CharCode, item.price * (1 + markup / 100)) | formatUsd }})
                    </v-col>
                </v-row>
            </v-container>
        </template>
        <template v-slot:item.amount="{ item }">
            <v-container>
                <v-row dense>
                    <v-col>
                        <b>
                            {{ toRub(item.CharCode, item.price * item.orderQuantity) | formatRub }}
                            ({{ toUsd(item.CharCode, item.price * item.orderQuantity) | formatUsd }})
                        </b>
                    </v-col>
                </v-row>
                <v-row dense>
                    <v-col class="text-caption">
                        {{ toRub(item.CharCode, retailPrice(item) * item.orderQuantity) | formatRub }}
                        ({{ toUsd(item.CharCode, retailPrice(item) * item.orderQuantity) | formatUsd }})
                    </v-col>
                </v-row>
                <v-row dense>
                    <v-col>
                        {{ toRub(item.CharCode, item.price * item.orderQuantity * (1 + markup / 100))  | formatRub }}
                        ({{ toUsd(item.CharCode, item.price * item.orderQuantity * (1 + markup / 100)) | formatUsd }})
                    </v-col>
                </v-row>
            </v-container>
        </template>
        <template v-slot:item.deliveryTime="{ item }">
            <v-row dense>
                <v-col>
                    <b>{{ item.deliveryTime }} дней</b>
                </v-col>
            </v-row>
            <v-row dense>
                <v-col class="text-caption">
                    {{ sellerRemark(item) }}
                </v-col>
            </v-row>
            <v-row dense>
                <v-col>
                    <v-btn :disabled="!item.isCache" small plain @click="update(item)">
                        {{ item.updatedAt | formatDateTime}}
                    </v-btn>
                </v-col>
            </v-row>
        </template>
    </v-data-table>
</template>

<script>
import { mapGetters } from 'vuex';
import SellerApiFileSelect from "./SellerApiFileSelect.vue";
import GoodInString from "./good/GoodInString";
import GoodSelect from "./good/GoodSelect";

export default {
    name: "SellerPrices",
    components: {GoodSelect, GoodInString, SellerApiFileSelect},
    data() {
        return {
            loading: false,
            options: {},
            loadingText: 'Еще чуть-чуть ...',
            search: '',
            handlers: [],
            markup: 20,
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
        }),
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
            let { search } = this;
            if (!search || search.trim().length  < 3) return;
            try {
                this.$store.commit('SELLER-PRICE/CLEAR_DATA');
                this.$store.commit('SELLER-PRICE/CLEAR_ALL_API_ERRORS');
                const selectedSellerId = this.$store.getters['SELLER-PRICE/SELECTED_SELLER_ID'];
                this.$store.commit('SELLER-PRICE/SELLER_SELECT', selectedSellerId);
                await Promise.all(this.handlers);
                this.loading = true;
                this.handlers = this.sellers
                    .filter((seller) => seller.isApi)
                    .map((seller) => {
                        seller.loading = true;
                        return this.$store.dispatch('SELLER-PRICE/GET', {sellerId: seller.sellerId, search})
                            .then(() => { seller.loading = false; }
                        )
                    })
                await Promise.all(this.handlers);
            } catch (e) {
                console.log(e)
            } finally {
                this.loading = false;
            }
        }, 1000),
        async items(items) {
            const goodIds = _.filter(
                _.filter(items, 'goodId').map((item) => item.goodId),
                (id) => !this.$store.getters['GOOD/GET'](id)
            );
            if (goodIds.length > 0) {
                await this.$store.dispatch('GOOD/ALL', {

                        with: [
                            'retailPrice', 'orderStep', 'retailStore', 'warehouse', 'name', 'category', 'goodNames'
                        ],
                        aggregateAttributes: [
                            'reservesQuantity',
                            'invoiceLinesQuantityTransit',
                            'reservesQuantityTransit',
                            'pickUpsTransitQuantity',
                            'retailOrderLinesNeedQuantity',
                            'orderLinesTransitQuantity',
                            'shopLinesTransitQuantity',
                            'storeLinesTransitQuantity',
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
        showName(item) {
            let ret = item.name;
            if (item.case && item.case.trim().length > 0) ret += ' / ' + item.case;
            if (item.producer && item.producer.trim().length > 0) ret += ' / ' + item.producer;
            return ret;
        },
        good(id) {
            return  this.$store.getters['GOOD/GET'](id);
        },
        showRemark(item) {
            let ret = item.remark ? item.remark.trim() : item.remark;
            return ret || 'Без комментариев';
        },
        sellerRemark(item) {
            if (!item.options) return 'С К Л А Д';
            if (item.options.location_id) return item.options.location_id;
            return item.options.vend_type;
        }
    }
}
</script>

<style scoped>
.sticky {
    position: sticky;
    top: var(--toolbarHeight);
    z-index: 1;
}
</style>
