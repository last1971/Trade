<template>
    <v-menu>
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
                            @click="seller.isApi = true"
                    >{{ seller.name }}</v-chip>
                </v-chip-group>
            </v-card-text>
        </v-card>
    </v-menu>
</template>

<script>
import {mapGetters} from "vuex";

export default {
    name: "SellerApiDisabled",
    computed: {
        ...mapGetters({
            sellers: 'SELLER-PRICE/SELLERS',
        }),
        notActiveSellers() {
            return this.sellers.filter((v) => !v.isApi).length;
        }
    }
}
</script>

<style scoped>

</style>
