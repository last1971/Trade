<template>
    <v-dialog v-model="dialog" max-width="400">
        <v-card>
            <v-card-title class="d-flex align-center">
                <span>Заказы {{ sellerName }}</span>
                <v-spacer/>
                <v-btn 
                    icon 
                    small
                    @click="sync" 
                    :loading="syncing"
                    title="Синхронизировать"
                >
                    <v-icon>mdi-refresh</v-icon>
                </v-btn>
            </v-card-title>
            
            <v-card-text v-if="loading" class="text-center py-5">
                <v-progress-circular indeterminate color="primary"/>
            </v-card-text>
            
            <v-card-text v-else-if="orders.length === 0" class="text-center py-5 grey--text">
                Нет заказов
            </v-card-text>
            
            <v-card-text v-else class="pa-0">
                <v-list>
                    <v-list-item
                        v-for="order in orders"
                        :key="order.id"
                        @click="selectOrder(order.id)"
                        :class="{ 'primary--text': isActive(order.id) }"
                    >
                        <v-list-item-icon>
                            <v-icon :color="isActive(order.id) ? 'primary' : 'grey'">
                                {{ isActive(order.id) ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                            </v-icon>
                        </v-list-item-icon>
                        
                        <v-list-item-content>
                            <v-list-item-title>
                                № {{ order.number }}
                            </v-list-item-title>
                            <v-list-item-subtitle>
                                {{ order.amount | formatRub }} | {{ order.date | formatDate }}
                            </v-list-item-subtitle>
                            <v-list-item-subtitle v-if="order.remark" class="text-caption">
                                {{ order.remark }}
                            </v-list-item-subtitle>
                        </v-list-item-content>
                    </v-list-item>
                </v-list>
            </v-card-text>
            
            <v-divider/>
            
            <v-card-text>
                <v-text-field
                    v-model="newOrderComment"
                    label="Комментарий для нового заказа"
                    outlined
                    dense
                    :disabled="creating"
                    hint="Обязательно для создания заказа"
                    persistent-hint
                />
            </v-card-text>
            
            <v-card-actions>
                <v-btn 
                    color="primary"
                    :disabled="!newOrderComment || creating"
                    :loading="creating"
                    @click="createOrder"
                >
                    Создать заказ
                </v-btn>
                <v-spacer/>
                <v-btn text @click="close">Отмена</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
import {mapGetters} from "vuex";

export default {
    name: "SellerOrderSelectDialog",
    props: {
        value: {
            type: Boolean,
            default: false
        },
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
            loading: false,
            syncing: false,
            creating: false,
            newOrderComment: ''
        }
    },
    computed: {
        ...mapGetters({
            getOrdersBySeller: 'SELLER-ORDER/GET_BY_SELLER',
            getActiveId: 'SELLER-ORDER/GET_ACTIVE_ID',
        }),
        dialog: {
            get() {
                return this.value;
            },
            set(val) {
                this.$emit('input', val);
            }
        },
        orders() {
            return this.getOrdersBySeller(this.sellerId);
        },
        activeOrderId() {
            return this.getActiveId(this.sellerId);
        }
    },
    watch: {
        async dialog(val) {
            if (val && this.orders.length === 0) {
                await this.loadOrders();
            }
        }
    },
    methods: {
        async loadOrders() {
            this.loading = true;
            try {
                await this.$store.dispatch('SELLER-ORDER/SYNC_SELLER', this.sellerId);
            } catch (e) {
                // Ошибка уже показана в экшене
            } finally {
                this.loading = false;
            }
        },
        async sync() {
            this.syncing = true;
            try {
                await this.$store.dispatch('SELLER-ORDER/SYNC_SELLER', this.sellerId);
                this.$store.commit('SNACKBAR/PUSH', { text: 'Заказы обновлены', color: 'success', status: true }, { root: true });
            } finally {
                this.syncing = false;
            }
        },
        selectOrder(orderId) {
            this.$store.commit('SELLER-ORDER/SET_ACTIVE_ID', {
                sellerId: this.sellerId,
                orderId: orderId
            });
            
            // Предзагружаем строки заказа фоном
            this.$store.dispatch('SELLER-ORDER/PRELOAD_LINES', {
                salesId: orderId,
                sellerId: this.sellerId
            });
            
            this.close();
        },
        isActive(orderId) {
            return this.activeOrderId === orderId;
        },
        async createOrder() {
            if (!this.newOrderComment) {
                return;
            }
            
            this.creating = true;
            try {
                const response = await axios.post('/api/seller-order', {
                    item: {
                        seller_id: this.sellerId,
                        remark: this.newOrderComment,
                        reserve_sale: true
                    }
                });
                
                // Добавляем новый заказ в стор
                this.$store.commit('SELLER-ORDER/ADD_ORDER', {
                    sellerId: this.sellerId,
                    order: response.data
                });
                
                // Выбираем созданный заказ
                this.$store.commit('SELLER-ORDER/SET_ACTIVE_ID', {
                    sellerId: this.sellerId,
                    orderId: response.data.id
                });
                
                this.$store.commit('SNACKBAR/PUSH', { text: `Заказ №${response.data.number} создан`, color: 'success', status: true }, { root: true });
                
                // Очищаем поле и закрываем диалог
                this.newOrderComment = '';
                this.close();
            } catch (e) {
                this.$store.commit('SNACKBAR/ERROR', e.response?.data?.message || 'Ошибка создания заказа', { root: true });
            } finally {
                this.creating = false;
            }
        },
        close() {
            this.dialog = false;
        }
    }
}
</script>

