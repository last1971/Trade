<template>
    <v-expansion-panels>
        <v-expansion-panel>
            <v-expansion-panel-header>
                <v-row dense>
                    Активных поставщиков {{ activeSellers }}
                    <v-progress-linear v-if="false" indeterminate></v-progress-linear>
                </v-row>
            </v-expansion-panel-header>
            <v-expansion-panel-content>
                <div class="d-flex">
                    <invoice-card class="flex-grow-1 flex-shrink-0"/>
                    <v-card class="m-1 flex-grow-0 flex-shrink-1 d-flex align-center">
                        <v-chip-group column class="mx-3">
                            <seller-api-chip v-for="seller in sellers"
                                            :key="seller.sellerId"
                                            :seller="seller"
                            />
                            <seller-api-disabled/>
                        </v-chip-group>
                    </v-card>
                </div>
            </v-expansion-panel-content>
        </v-expansion-panel>
    </v-expansion-panels>
</template>

<script>
import {mapGetters} from "vuex";
import SellerApiChip from "./SellerApiChip";
import InvoiceCard from '../../invoice/InvoiceCard'
import SellerApiDisabled from "./SellerApiDisabled";

export default {
    name: "SellerApiFileSelectNew",
    components: {SellerApiDisabled, SellerApiChip, InvoiceCard},
    data() {
        return {
            o: false,
        }
    },
    computed: {
        ...mapGetters({
            sellers: 'SELLER-PRICE/SELLERS',
        }),
        activeSellers() {
            return this.sellers.filter((v) => v.isApi).length;
        }
    },
    methods: {

    }
}
</script>

<style scoped>

</style>
