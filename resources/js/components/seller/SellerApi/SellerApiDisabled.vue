<template>
    <div>
        <v-menu v-if="notActiveSellers > 1">
            <template v-slot:activator="{ on }">
                <v-chip v-on="on">
                    Не активных поставищиков - {{ notActiveSellers }}
                </v-chip>
            </template>
            <v-card>
                <v-card-title>
                    Не активные поставщики
                </v-card-title>
                <v-divider></v-divider>
                <v-card-text>
                    <v-chip-group column >
                        <v-chip v-for="seller in sellers"
                                :key="seller.sellerId"
                                v-if="!seller.isApi"
                                @click="setSellerActive(seller)"
                        >{{ seller.name }}</v-chip>
                    </v-chip-group>
                </v-card-text>
            </v-card>
        </v-menu>
        <v-chip v-else @click="setSellerActive(notActiveSeller)">
            {{ notActiveSeller.name }}
        </v-chip>
    </div>
</template>

<script>
import {mapGetters} from "vuex";
import {find} from "lodash";

export default {
    name: "SellerApiDisabled",
    computed: {
        ...mapGetters({
            sellers: 'SELLER-PRICE/SELLERS',
        }),
        notActiveSellers() {
            return this.sellers.filter((v) => !v.isApi).length;
        },
        notActiveSeller() {
            return find(this.sellers, (v) => !v.isApi);
        }
    },
    methods: {
        setSellerActive(seller) {
            seller.isApi = true;
            this.$emit('save-sellers');
            this.$emit('seller-on', seller)
        },
    }
}
</script>

<style scoped>

</style>
