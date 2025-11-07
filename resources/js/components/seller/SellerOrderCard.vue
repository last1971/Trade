<template>
    <v-card 
        outlined 
        class="ma-1" 
        :class="{ 'primary--text': hasActiveOrder }"
        @click="openDialog"
        style="cursor: pointer"
    >
        <v-card-text class="pa-2">
            <v-row dense align="center">
                <v-col>
                    <div class="font-weight-bold">{{ sellerName }}</div>
                    <div v-if="activeOrder" class="text-body-2">
                        № {{ activeOrder.number }}
                    </div>
                    <div v-else class="text-body-2 grey--text">
                        Выберите заказ...
                    </div>
                </v-col>
                <v-col v-if="activeOrder" cols="auto" class="text-right">
                    <div class="text-body-2">{{ activeOrder.amount | formatRub }}</div>
                    <div class="text-caption grey--text">{{ activeOrder.date | formatDate }}</div>
                </v-col>
                <v-col v-if="activeOrder" cols="auto">
                    <v-btn 
                        icon 
                        x-small 
                        @click.stop="removeOrder"
                        color="error"
                    >
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </v-col>
            </v-row>
        </v-card-text>
        
        <!-- Диалог выбора заказа -->
        <seller-order-select-dialog
            v-model="showDialog"
            :seller-id="sellerId"
            :seller-name="sellerName"
        />
    </v-card>
</template>

<script>
import {mapGetters} from "vuex";
import SellerOrderSelectDialog from "./SellerOrderSelectDialog";

export default {
    name: "SellerOrderCard",
    components: {SellerOrderSelectDialog},
    props: {
        sellerId: {
            type: Number,
            required: true
        },
        sellerName: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            showDialog: false
        }
    },
    computed: {
        ...mapGetters({
            getActiveOrder: 'SELLER-ORDER/GET_ACTIVE_ORDER',
        }),
        activeOrder() {
            return this.getActiveOrder(this.sellerId);
        },
        hasActiveOrder() {
            return !!this.activeOrder;
        }
    },
    methods: {
        openDialog() {
            this.showDialog = true;
        },
        removeOrder() {
            this.$store.commit('SELLER-ORDER/REMOVE_ACTIVE_ID', this.sellerId);
        }
    }
}
</script>

