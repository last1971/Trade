<template>
    <v-dialog v-model="isActiveProxy">
        <template v-if="withActivator" v-slot:activator="{ on }">
            <v-btn v-on="on"
                   :x-small="xSmall"
                   fab
                   :disabled="disabled"
            >
                <v-icon color="primary">mdi-playlist-plus</v-icon>
            </v-btn>
        </template>
        <v-card>
            <v-card-title class="headline">
                <span class="headline">Платежка</span>
                <v-spacer/>
                <v-btn @click="isActiveProxy = false" icon right>
                    <v-icon color="red">
                        mdi-close
                    </v-icon>
                </v-btn>
            </v-card-title>
            <v-container>
                <v-row>
                    <v-col>
                        <date-picker v-model="cashFlow.DATA" label="Дата" :disabled="false"/>
                    </v-col>
                    <v-col>
                        <v-text-field
                            label="П/П"
                            v-model="cashFlow.NPP"
                            :rules="[rules.isInteger, rules.positive]"
                        />
                    </v-col>
                    <v-col>
                        <v-text-field
                            label="Сумма"
                            v-model="cashFlow.MONEYSCHET"
                            :rules="[rules.isNumber, rules.positive, rules.required]"
                        />
                    </v-col>
                    <v-col>
                        <v-text-field label="Примечание" v-model="cashFlow.PRIM" />
                    </v-col>
                    <v-col>
                        <transfer-out-select :invoice="value" v-model="cashFlow.SFCODE1" @change="change"/>
                    </v-col>
                    <v-col>
                        <v-btn :disabled="!savePossible" @click="save" fab :loading="loading">
                            <v-icon color="green">mdi-content-save</v-icon>
                        </v-btn>
                    </v-col>
                </v-row>
            </v-container>
        </v-card>
    </v-dialog>
</template>

<script>
    import DatePicker from "./DatePicker.vue";
    import utilsMixin from "../mixins/utilsMixin";
    import moment from "moment";
    import TransferOutSelect from "./transferOut/TransferOutSelect.vue";

    export default {
        name: 'CashFlowAdd',
        mixins:[utilsMixin],
        components: {TransferOutSelect, DatePicker},
        props: {
            value: {
                type: Object,
                required: true,
            },
            xSmall: {
                type: Boolean,
                default: false,
            },
            withActivator: {
                type: Boolean,
                default: true,
            },
            isActive: {
                type: Boolean,
                default: false,
            },
            disabled: {
                type: Boolean,
                default: false,
            },
        },
        data() {
            return {
                isActiveProxy: this.isActive,
                cashFlow: {
                    MONEYSCHET: 0,
                    NS: this.value.NS,
                    DATA: moment().format('Y-MM-DD'),
                    POKUPATCODE: this.value.POKUPATCODE,
                    NPP: null,
                    SCODE: this.value.SCODE,
                    PRIM: null,
                    SFCODE1: null,
                },
                loading: false,
            }
        },
        computed: {
            savePossible() {
                const { rules } = this;
                return this.$_utilsMixin_isValid(
                    this.cashFlow.MONEYSCHET, [rules.isNumber, rules.required, rules.positive]
                ) && (!this.cashFlow.NPP || this.$_utilsMixin_isValid(this.cashFlow.NPP, [rules.isInteger,
                    rules.positive]));
            },
        },
        methods: {
            async save() {
                try {
                    this.loading = true;
                    await this.$store.dispatch(
                        'CASH-FLOW/CREATE',
                        {
                            item: this.cashFlow,
                            options: {},
                        },
                    );
                    this.cashFlow = {
                        MONEYSCHET: 0,
                        NS: this.value.NS,
                        DATA: moment().format('Y-MM-DD'),
                        POKUPATCODE: this.value.POKUPATCODE,
                        NPP: null,
                        SCODE: this.value.SCODE,
                        PRIM: null,
                        SFCODE1: null,
                    };
                    this.$emit('updated')
                } catch (e) {

                }
                this.loading = false;
                this.isActiveProxy = false;
            },
            change(payload) {
                this.cashFlow.SFCODE1 = payload.SFCODE;
                this.cashFlow.MONEYSCHET = payload.transferOutLinesSum;
            }
        }
    }
</script>

<style scoped>

</style>
