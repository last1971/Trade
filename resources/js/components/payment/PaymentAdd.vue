<template>
    <v-dialog v-model="adding" max-width="800">
        <template v-slot:activator="{ on }">
            <v-btn rounded color="success" v-on="on">
                <v-icon left>mdi-plus</v-icon>
                Новый платеж
            </v-btn>
        </template>
        <v-card>
            <v-card-title class="headline">
                <span class="headline">Создать новый платеж</span>
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
                            :value="payment.date | formatDate"
                            label="Дата"
                            prepend-icon="mdi-calendar-edit"
                            readonly
                            v-on="on"
                        />
                    </template>
                    <v-date-picker @input="datePicker = false" first-day-of-week="1" v-model="payment.date" />
                </v-menu>
                <v-text-field label="Номер" v-model="payment.number" />
                <seller-select v-model="payment.seller_id"/>
                <v-text-field label="Сумма"
                              v-model="payment.amount"
                              :rules="[rules.isNumber, rules.required, rules.positive]"
                />
                <v-text-field label="Порядок"
                              v-model="payment.weight"
                              :rules="[rules.isInteger, rules.required, rules.positive]"
                />
                <v-menu
                    :close-on-content-click="false"
                    :nudge-right="40"
                    min-width="290px"
                    offset-y
                    transition="scale-transition"
                    v-model="payBeforePicker"
                >
                    <template v-slot:activator="{ on }">
                        <v-text-field
                            :value="payment.pay_before | formatDate"
                            label="Дата"
                            prepend-icon="mdi-calendar-edit"
                            readonly
                            v-on="on"
                        />
                    </template>
                    <v-date-picker @input="payBeforePicker = false" first-day-of-week="1" v-model="payment.pay_before" />
                </v-menu>
                <v-text-field label="Примечание" v-model="payment.comment" />
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
import SellerSelect from "../seller/SellerSelect";

export default {
    name: "PaymentAdd",
    components: {SellerSelect},
    mixins:[utilsMixin],
    data() {
        return {
            adding: false,
            datePicker: false,
            payBeforePicker: false,
            payment: {
                date: moment().format('Y-MM-DD'),
                number: null,
                seller_id: null,
                amount: null,
                weight: 0,
                pay_before: moment().format('Y-MM-DD'),
                comment: '',
            },
        }
    },
    computed: {
        saveNotPossible() {
            return this.rules.required(this.payment.amount) !== true
                || this.rules.isNumber(this.payment.amount) !== true
                || this.rules.positive(this.payment.amount) !== true
                || !this.payment.seller_id
                || this.rules.required(this.payment.weight) !== true
                || this.rules.isNumber(this.payment.weight) !== true
                || this.rules.positive(this.payment.weight) !== true;
        }
    },
    methods: {
        close() {
            this.adding = false;
        },
        async save() {
            try {
                await this.$store.dispatch('PAYMENT/CREATE', {item: this.payment});
                this.payment = {
                    date: moment().format('Y-MM-DD'),
                    number: null,
                    seller_id: null,
                    amount: null,
                    weight: 0,
                    pay_before: moment().format('Y-MM-DD'),
                    comment: '',
                };
                this.$emit('reload');
                this.close();
            } catch (e) {
                console.error(e);
            }
        }
    }
}
</script>

<style scoped>

</style>
