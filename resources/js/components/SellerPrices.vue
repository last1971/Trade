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
    >
        <template v-slot:top>
            <v-card>
                <v-card-text>
                    <v-text-field label="Строка поиска"
                                  placeholder="Введите название комплнента"
                                  v-model="search"

                    />
                    <v-switch v-model="isAll" :label="isAllLabel"/>
                </v-card-text>
            </v-card>
        </template>
    </v-data-table>
</template>

<script>
import { mapGetters } from 'vuex';

export default {
    name: "SellerPrices",
    data() {
        return {
            loading: false,
            options: {},
            loadingText: 'Еще чуть-чуть ...',
            search: '',
        }
    },
    computed: {
        ...mapGetters({
            headers: 'SELLER-PRICE/HEADERS',
            items: 'SELLER-PRICE/FILTERD_DATA',
            retailPrice: 'SELLER-PRICE/RETAIL_PRICE',
            isInput: 'SELLER-PRICE/IS_INPUT',
        }),
        isAll: {
            get() {
                return this.$store.getters['SELLER-PRICE/IS_ALL'];
            },
            set(v) {
                this.$store.commit('SELLER-PRICE/SET_IS_ALL', v);
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
            this.loading = true;
            await this.$store.dispatch('SELLER-PRICE/GET', { sellerId: 0, search });
            this.loading = false;
        }, 1000),
    },
    async created() {

    }
}
</script>

<style scoped>

</style>
