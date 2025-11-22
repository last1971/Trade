<template>
    <v-dialog v-model="dialog" max-width="900">
        <v-card>
            <v-card-title class="d-flex flex-column align-start">
                <div class="d-flex w-100 align-center">
                    <div>
                        <div>Строки заказа № {{ order.number }}</div>
                        <div class="text-caption grey--text">
                            {{ order.date | formatDate }}
                            <span v-if="order.remark"> • {{ order.remark }}</span>
                        </div>
                        <div v-if="order.document_number || order.date_deadline" class="text-caption grey--text mt-1">
                            <span v-if="order.document_number">Номер счета: <strong>{{ order.document_number }}</strong></span>
                            <span v-if="order.document_number && order.date_deadline"> • </span>
                            <span v-if="order.date_deadline">Дата действия счета: <strong>{{ order.date_deadline | formatDate }}</strong></span>
                        </div>
                    </div>
                    <v-spacer/>
                    <v-btn 
                        icon 
                        @click="refreshLines" 
                        :loading="loading"
                        title="Обновить"
                    >
                        <v-icon>mdi-refresh</v-icon>
                    </v-btn>
                    <v-btn icon @click="close">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </div>
            </v-card-title>
            
            <v-card-text v-if="loading" class="text-center py-5">
                <v-progress-circular indeterminate color="primary"/>
            </v-card-text>
            
            <v-card-text v-else-if="lines.length === 0" class="text-center py-5 grey--text">
                Нет строк в заказе
            </v-card-text>
            
            <v-card-text v-else class="pa-0">
                <v-simple-table dense>
                    <template v-slot:default>
                        <thead>
                            <tr>
                                <th>Код</th>
                                <th>Наименование</th>
                                <th>Производитель</th>
                                <th>Корпус</th>
                                <th class="text-right">Кол-во</th>
                                <th class="text-right">Резерв</th>
                                <th class="text-right">Цена</th>
                                <th class="text-right">Сумма</th>
                                <th>Срок резерва</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="line in lines" :key="line.line_id">
                                <td>{{ line.item_id }}</td>
                                <td>{{ line.item_name }}</td>
                                <td>{{ line.brend }}</td>
                                <td>{{ line.package_name }}</td>
                                <td class="text-right">
                                    <v-edit-dialog
                                        :return-value.sync="line.sales_qty"
                                        @save="updateQuantity(line)"
                                        @open="editingQuantity = line.sales_qty"
                                        large
                                        persistent
                                    >
                                        <div style="cursor: pointer">{{ line.sales_qty }}</div>
                                        <template v-slot:input>
                                            <v-text-field
                                                v-model.number="editingQuantity"
                                                label="Количество"
                                                single-line
                                                type="number"
                                                :min="1"
                                                autofocus
                                            />
                                        </template>
                                    </v-edit-dialog>
                                </td>
                                <td class="text-right">
                                    <v-chip 
                                        x-small 
                                        :color="line.reserve_qty > 0 ? 'success' : 'grey'"
                                    >
                                        {{ line.reserve_qty }}
                                    </v-chip>
                                </td>
                                <td class="text-right">
                                    {{ priceInRub(line) | formatRub }}
                                </td>
                                <td class="text-right">
                                    <strong>{{ amountInRub(line) | formatRub }}</strong>
                                </td>
                                <td>
                                    <span v-if="line.reservation_end" class="text-caption">
                                        {{ line.reservation_end | formatDate }}
                                    </span>
                                    <span v-else class="grey--text text-caption">—</span>
                                </td>
                                <td>
                                    <v-btn
                                        icon
                                        x-small
                                        color="error"
                                        @click="confirmDelete(line)"
                                        :disabled="loading"
                                    >
                                        <v-icon small>mdi-delete</v-icon>
                                    </v-btn>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6" class="text-right"><strong>Итого:</strong></td>
                                <td class="text-right">
                                    <strong>{{ totalAmount | formatRub }}</strong>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </template>
                </v-simple-table>
            </v-card-text>
            
            <v-card-actions>
                <v-btn
                    color="primary"
                    @click="sendInvoice"
                    :loading="sendingInvoice"
                    :disabled="loading || !lines.length"
                    v-if="showSendInvoiceButton"
                >
                    <v-icon left>mdi-email-send</v-icon>
                    Отправить счет
                </v-btn>
                <v-btn
                    color="success"
                    @click="openShipDialog"
                    :disabled="loading || !lines.length"
                    v-if="isCompel"
                >
                    <v-icon left>mdi-truck-delivery</v-icon>
                    Отгрузить
                </v-btn>
                <v-btn
                    color="success"
                    @click="shipPromelecOrder"
                    :loading="shippingPromelec"
                    :disabled="loading || !lines.length"
                    v-if="isPromelec"
                >
                    <v-icon left>mdi-truck-delivery</v-icon>
                    Отгрузить
                </v-btn>
                <v-spacer/>
                <v-btn text @click="close">Закрыть</v-btn>
            </v-card-actions>
        </v-card>
        
        <!-- Диалог подтверждения удаления -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title>Удалить строку?</v-card-title>
                <v-card-text v-if="lineToDelete">
                    <div>{{ lineToDelete.item_name }}</div>
                    <div class="text-caption grey--text">{{ lineToDelete.brend }}</div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer/>
                    <v-btn text @click="deleteDialog = false">Нет</v-btn>
                    <v-btn color="error" text @click="deleteLine" :loading="deleting">Да</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
        
        <!-- Диалог отгрузки -->
        <ship-order-dialog ref="shipDialog" @shipped="handleShipped" />
    </v-dialog>
</template>

<script>
import {mapGetters} from "vuex";
import ShipOrderDialog from "./ShipOrderDialog";

export default {
    name: "SellerOrderLinesDialog",
    components: {
        ShipOrderDialog
    },
    props: {
        value: {
            type: Boolean,
            default: false
        },
        order: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            loading: false,
            editingQuantity: 0,
            deleteDialog: false,
            lineToDelete: null,
            deleting: false,
            sendingInvoice: false,
            shippingPromelec: false
        }
    },
    computed: {
        ...mapGetters({
            getOrderLines: 'SELLER-ORDER/GET_ORDER_LINES',
            toRub: 'EXCHANGE-RATE/TO_RUB'
        }),
        dialog: {
            get() {
                return this.value;
            },
            set(val) {
                this.$emit('input', val);
            }
        },
        cachedData() {
            return this.getOrderLines(this.order.id);
        },
        lines() {
            return this.cachedData.lines || [];
        },
        totalAmount() {
            return this.lines.reduce((sum, line) => {
                return sum + this.amountInRub(line);
            }, 0);
        },
        isCompel() {
            // Compel (857) и CompelDms (1279) используют openShipDialog
            return [857, 1279].includes(this.order.seller_id);
        },
        isPromelec() {
            // Все остальные поставщики используют простую отгрузку
            return ![857, 1279].includes(this.order.seller_id);
        },
        showSendInvoiceButton() {
            // Показываем кнопку для всех поставщиков
            return true;
        }
    },
    watch: {
        async dialog(val) {
            if (val) {
                await this.loadLines();
            }
        }
    },
    methods: {
        async loadLines(forceReload = false) {
            this.loading = true;
            try {
                await this.$store.dispatch('SELLER-ORDER/GET_LINES', {
                    salesId: this.order.id,
                    sellerId: this.order.seller_id,
                    forceReload: forceReload
                });
            } catch (e) {
                // Ошибка уже показана в экшене
            } finally {
                this.loading = false;
            }
        },
        async refreshLines() {
            await this.loadLines(true);
        },
        priceInRub(line) {
            // Если уже в рублях - возвращаем как есть
            if (line.currency_code === 'RUB') {
                return parseFloat(line.price) || 0;
            }
            // Иначе конвертируем
            return this.toRub(line.currency_code, line.price, this.order.seller_id);
        },
        amountInRub(line) {
            // Если уже в рублях - возвращаем как есть
            if (line.currency_code === 'RUB') {
                return parseFloat(line.amount) || 0;
            }
            // Иначе конвертируем
            return this.toRub(line.currency_code, line.amount);
        },
        async updateQuantity(line) {
            if (!this.editingQuantity || this.editingQuantity < 1) {
                this.$store.commit('SNACKBAR/ERROR', 'Количество должно быть больше 0', { root: true });
                return;
            }
            
            if (this.editingQuantity === line.sales_qty) {
                return; // Не изменилось
            }
            
            this.loading = true;
            try {
                // Вычисляем разницу в сумме
                const oldAmount = this.amountInRub(line);
                const newAmount = this.priceInRub(line) * this.editingQuantity;
                const amountDiff = newAmount - oldAmount;
                
                await this.$store.dispatch('SELLER-ORDER/UPDATE_LINE_QUANTITY', {
                    salesId: this.order.id,
                    sellerId: this.order.seller_id,
                    lineId: line.line_id,
                    quantity: this.editingQuantity,
                    amountDiff: amountDiff
                });
                
                this.$store.commit('SNACKBAR/PUSH', { text: 'Количество изменено', color: 'success', status: true }, { root: true });
            } catch (e) {
                // Ошибка уже показана в экшене
            } finally {
                this.loading = false;
            }
        },
        confirmDelete(line) {
            this.lineToDelete = line;
            this.deleteDialog = true;
        },
        async deleteLine() {
            if (!this.lineToDelete) return;
            
            this.deleting = true;
            try {
                const amountToSubtract = this.amountInRub(this.lineToDelete);
                
                await this.$store.dispatch('SELLER-ORDER/DELETE_LINE', {
                    salesId: this.order.id,
                    sellerId: this.order.seller_id,
                    lineId: this.lineToDelete.line_id,
                    amountToSubtract: amountToSubtract
                });
                
                this.$store.commit('SNACKBAR/PUSH', { text: 'Строка удалена', color: 'success', status: true }, { root: true });
                this.deleteDialog = false;
                this.lineToDelete = null;
            } catch (e) {
                // Ошибка уже показана в экшене
            } finally {
                this.deleting = false;
            }
        },
        async sendInvoice() {
            this.sendingInvoice = true;
            try {
                const response = await axios.post(
                    `/api/seller-order/${this.order.id}/send-invoice`,
                    null,
                    {
                        params: {
                            seller_id: this.order.seller_id
                        }
                    }
                );
                
                if (response.data.success && response.data.data) {
                    const data = response.data.data;

                    // Если это Excel в base64 (Firebird) - скачиваем файл
                    if (data.type === 'excel' && data.excel) {
                        const blob = this.base64ToBlob(data.excel, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                        const url = URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = data.filename || 'order.xlsx';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        URL.revokeObjectURL(url);
                        this.$store.commit('SNACKBAR/PUSH', {
                            text: 'Файл заказа скачан',
                            color: 'success',
                            status: true
                        }, { root: true });
                        return;
                    }

                    // Если это PDF в base64 (Promelec) - открываем в новой вкладке
                    if (data.type === 'pdf' && data.pdf) {
                        const blob = this.base64ToBlob(data.pdf, 'application/pdf');
                        const url = URL.createObjectURL(blob);
                        window.open(url, '_blank');
                        this.$store.commit('SNACKBAR/PUSH', {
                            text: 'Счет открыт в новой вкладке',
                            color: 'success',
                            status: true
                        }, { root: true });
                        return;
                    }

                    // Если это ссылка - открываем в новой вкладке
                    if (data.type === 'link' && data.url) {
                        window.open(data.url, '_blank');
                        this.$store.commit('SNACKBAR/PUSH', {
                            text: 'Счет открыт в новой вкладке',
                            color: 'success',
                            status: true
                        }, { root: true });
                        return;
                    }

                    // Иначе обновляем поля заказа (Compel)
                    const fields = {};

                    if (data.official_doc_num_invoice) {
                        fields.document_number = data.official_doc_num_invoice;
                    }

                    if (data.date_deadline) {
                        fields.date_deadline = data.date_deadline;
                    }

                    if (Object.keys(fields).length > 0) {
                        this.$store.commit('SELLER-ORDER/UPDATE_ORDER_FIELDS', {
                            orderId: this.order.id,
                            fields: fields
                        });
                    }

                    this.$store.commit('SNACKBAR/PUSH', {
                        text: 'Счет отправлен',
                        color: 'success',
                        status: true
                    }, { root: true });
                }
            } catch (e) {
                this.$store.commit('SNACKBAR/ERROR', 
                    e.response?.data?.message || 'Ошибка отправки счета', 
                    { root: true }
                );
            } finally {
                this.sendingInvoice = false;
            }
        },
        close() {
            this.dialog = false;
        },
        openShipDialog() {
            this.$refs.shipDialog.open(this.order);
        },
        handleShipped(orderId) {
            // Удаляем заказ из списка
            this.$emit('order-shipped', orderId);
            // Закрываем диалог строк
            this.close();
        },
        base64ToBlob(base64, contentType) {
            const byteCharacters = atob(base64);
            const byteNumbers = new Array(byteCharacters.length);
            for (let i = 0; i < byteCharacters.length; i++) {
                byteNumbers[i] = byteCharacters.charCodeAt(i);
            }
            const byteArray = new Uint8Array(byteNumbers);
            return new Blob([byteArray], { type: contentType });
        },
        async shipPromelecOrder() {
            this.shippingPromelec = true;
            try {
                const response = await axios.post(
                    `/api/seller-order/${this.order.id}/ship`,
                    {
                        seller_id: this.order.seller_id
                    }
                );
                
                if (response.data.success) {
                    this.$store.commit('SNACKBAR/PUSH', {
                        text: 'Заказ успешно отгружен',
                        color: 'success',
                        status: true
                    }, { root: true });
                    
                    // Удаляем заказ из списка (статус изменился)
                    this.$emit('order-shipped', this.order.id);
                    this.close();
                } else {
                    throw new Error(response.data.message || 'Ошибка отгрузки');
                }
            } catch (error) {
                const message = error.response?.data?.message || error.message || 'Ошибка отгрузки';
                this.$store.commit('SNACKBAR/ERROR', message, { root: true });
            } finally {
                this.shippingPromelec = false;
            }
        }
    }
}
</script>

<style scoped>

</style>

