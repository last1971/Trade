<template>
    <v-dialog v-model="adding" max-width="800">
        <template v-slot:activator="{ on }">
            <v-btn rounded color="success" v-on="on">
                <v-icon left>mdi-plus</v-icon>
                Новая платежка
            </v-btn>
        </template>
        <v-card>
            <v-card-title class="headline">
                <span class="headline">Создать новую платежку</span>
                <v-spacer/>
                <v-btn @click="close" icon right>
                    <v-icon color="red">
                        mdi-close
                    </v-icon>
                </v-btn>
            </v-card-title>
            <v-divider/>
            <v-card-text>
                <v-menu
                    :close-on-content-click="false"
                    :nudge-right="40"
                    min-width="290px"
                    offset-y
                    transition="scale-transition"
                    v-model="datePicker"
                >
                    <template v-slot:activator="{ on }">
                        <v-text-field
                            :value="paymentOrder.date | formatDate"
                            label="Дата"
                            prepend-icon="mdi-calendar-edit"
                            readonly
                            v-on="on"
                        />
                    </template>
                    <v-date-picker @input="datePicker = false" first-day-of-week="1" v-model="paymentOrder.date" />
                </v-menu>
                <v-text-field label="Номер" v-model="paymentOrder.number" :rules="[rules.required]"/>
                <v-text-field label="Сумма"
                              v-model="paymentOrder.amount"
                              :rules="[rules.isNumber, rules.required, rules.positive, rules.max(max)]"
                />
            </v-card-text>
            <v-card-actions class="d-flex justify-end">
                <v-btn rounded color="success" :disabled="saveNotPossible" @click="save">
                    <v-icon left>
                        mdi-content-save
                    </v-icon>
                    Сохранить
                </v-btn>
                <v-btn rounded color="error" @click="close">
                    <v-icon left>
                        mdi-close
                    </v-icon>
                    Отменить
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
import utilsMixin from "../../mixins/utilsMixin";
import moment from 'moment';

export default {
    name: "PaymentOrderAdd",
    mixins:[utilsMixin],
    props: {
        value: {
            type: Object,
            required: true,
        }
    },
    data() {
        return {
            adding: false,
            datePicker: false,
            paymentOrder: {
                date: moment().format('Y-MM-DD'),
                number: null,
                payment_id: this.value.id,
                amount: null,
            },
        }
    },
    computed: {
        max() {
            this.paymentOrder.amount = this.value.amount - (this.value.paid || 0);
            return this.value.amount - (this.value.paid || 0);
        },
        saveNotPossible() {
            return this.rules.required(this.paymentOrder.amount) !== true
                || this.rules.isNumber(this.paymentOrder.amount) !== true
                || this.rules.positive(this.paymentOrder.amount) !== true
                || this.rules.max(this.max)(this.paymentOrder.amount) !== true
                || this.rules.required(this.paymentOrder.number) !== true
        },
    },
    watch: {
        value() {
            this.paymentOrder.payment_id = this.value.id;
        }
    },
    methods: {
        close() {
            this.adding = false;
        },
        async save() {
            try {
                await this.$store.dispatch('PAYMENT-ORDER/CREATE', {item: this.paymentOrder});
                this.paymentOrder = {
                    date: moment().format('Y-MM-DD'),
                    number: null,
                    payment_id: this.value.id,
                    amount: null,
                };
                this.$emit('reload');
                this.close();
            } catch (e) {
                console.error(e);
            }
        }
    },
}
</script>

<style scoped>

</style>
