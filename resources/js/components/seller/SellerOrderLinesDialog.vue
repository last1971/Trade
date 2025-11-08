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
                                <th>Наименование</th>
                                <th>Производитель</th>
                                <th>Корпус</th>
                                <th class="text-right">Кол-во</th>
                                <th class="text-right">Резерв</th>
                                <th class="text-right">Цена</th>
                                <th class="text-right">Сумма</th>
                                <th>Срок резерва</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="line in lines" :key="line.line_id">
                                <td>{{ line.item_name }}</td>
                                <td>{{ line.brend }}</td>
                                <td>{{ line.package_name }}</td>
                                <td class="text-right">{{ line.sales_qty }}</td>
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
                <v-spacer/>
                <v-btn text @click="close">Закрыть</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
import {mapGetters} from "vuex";

export default {
    name: "SellerOrderLinesDialog",
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
            loading: false
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
                return line.price;
            }
            // Иначе конвертируем
            return this.toRub(line.currency_code, line.price);
        },
        amountInRub(line) {
            // Если уже в рублях - возвращаем как есть
            if (line.currency_code === 'RUB') {
                return line.amount;
            }
            // Иначе конвертируем
            return this.toRub(line.currency_code, line.amount);
        },
        close() {
            this.dialog = false;
        }
    }
}
</script>

<style scoped>

</style>

