<template>
    <v-dialog v-model="dialog" max-width="600" persistent>
        <v-card>
            <v-card-title>
                Отгрузка заказа № {{ order.number }}
            </v-card-title>
            
            <v-card-text>
                <v-form ref="form" v-model="valid">
                    <v-select
                        v-model="selectedDeliveryMode"
                        :items="deliveryModes"
                        item-text="delivery_mode"
                        item-value="id"
                        label="Способ доставки"
                        :rules="[v => !!v || 'Выберите способ доставки']"
                        required
                    >
                        <template v-slot:selection="{ item }">
                            <div>
                                <div>{{ item.delivery_mode }}</div>
                                <div class="text-caption grey--text">{{ item.address }}</div>
                            </div>
                        </template>
                        <template v-slot:item="{ item }">
                            <v-list-item-content>
                                <v-list-item-title>{{ item.delivery_mode }}</v-list-item-title>
                                <v-list-item-subtitle class="text-caption">
                                    {{ item.address }}
                                </v-list-item-subtitle>
                            </v-list-item-content>
                        </template>
                    </v-select>
                    
                    <date-picker
                        v-model="dateDeadline"
                        label="Дата действия"
                        :rules="[v => !!v || 'Выберите дату']"
                        required
                    />
                </v-form>
            </v-card-text>
            
            <v-card-actions>
                <v-spacer/>
                <v-btn text @click="close">
                    Отмена
                </v-btn>
                <v-btn 
                    color="primary" 
                    @click="ship" 
                    :loading="loading"
                    :disabled="!valid"
                >
                    Отгрузить
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
import { mapGetters } from 'vuex';
import DatePicker from '../DatePicker';
import createLocalStorageSync from '../../helpers/localStorage';

const deliveryModeStorage = createLocalStorageSync('compel_delivery_mode');

export default {
    name: 'ShipOrderDialog',
    components: { DatePicker },
    data() {
        return {
            dialog: false,
            order: {},
            valid: false,
            selectedDeliveryMode: null,
            dateDeadline: null,
            loading: false,
        };
    },
    computed: {
        ...mapGetters({
            deliveryModes: 'COMPEL/GET_DELIVERY_MODES',
        }),
    },
    watch: {
        selectedDeliveryMode(val) {
            if (val) {
                deliveryModeStorage.set(val);
            }
        },
    },
    methods: {
        open(order) {
            this.order = order;
            this.dialog = true;
            this.dateDeadline = null;
            this.loading = false;

            // Восстанавливаем последний выбранный способ доставки
            const savedMode = deliveryModeStorage.get(null);
            const modeExists = savedMode && this.deliveryModes.some(m => m.id === savedMode);
            this.selectedDeliveryMode = modeExists ? savedMode : null;
            
            // Устанавливаем дату по умолчанию - ближайшая пятница
            // До 9:00 МСК пятницы можно выбрать текущую пятницу
            const now = new Date();
            const moscowOffset = 3 * 60; // Москва UTC+3
            const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
            const moscowTime = new Date(utc + (moscowOffset * 60000));

            const dayOfWeek = moscowTime.getDay(); // 0 = воскресенье, 5 = пятница
            const hour = moscowTime.getHours();

            let daysUntilFriday;
            if (dayOfWeek === 5 && hour < 9) {
                // Пятница до 9 утра МСК - можно сегодня
                daysUntilFriday = 0;
            } else {
                daysUntilFriday = (5 - dayOfWeek + 7) % 7;
                if (daysUntilFriday === 0) {
                    // Пятница после 9 утра - следующая пятница
                    daysUntilFriday = 7;
                }
            }

            const targetDate = new Date(now);
            targetDate.setDate(now.getDate() + daysUntilFriday);
            this.dateDeadline = targetDate.toISOString().split('T')[0];
            
            this.$nextTick(() => {
                if (this.$refs.form) {
                    this.$refs.form.resetValidation();
                }
            });
        },
        close() {
            this.dialog = false;
        },
        async ship() {
            if (!this.$refs.form.validate()) {
                return;
            }
            
            this.loading = true;
            
            try {
                const response = await axios.post(
                    `/api/seller-order/${this.order.id}/ship`,
                    {
                        seller_id: this.order.seller_id,
                        customer_delivery_type_id: this.selectedDeliveryMode,
                        date_deadline: this.dateDeadline,
                    }
                );
                
                if (response.data.success) {
                    this.$store.commit('SNACKBAR/PUSH', {
                        text: 'Заказ успешно отгружен',
                        color: 'success',
                        status: true
                    }, { root: true });
                    
                    // Удаляем заказ из списка (статус изменился)
                    this.$emit('shipped', this.order.id);
                    
                    this.close();
                } else {
                    throw new Error(response.data.message || 'Ошибка отгрузки');
                }
            } catch (error) {
                const message = error.response?.data?.message || error.message || 'Ошибка отгрузки';
                this.$store.commit('SNACKBAR/ERROR', message, { root: true });
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>

