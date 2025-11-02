<template>
    <v-container>
        <v-row>
            <v-col>
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
                            :value="date | formatDate"
                            label="Дата"
                            prepend-icon="mdi-calendar-edit"
                            readonly
                            v-on="on"
                        />
                    </template>
                    <v-date-picker @input="datePicker = false" first-day-of-week="1" v-model="date">

                    </v-date-picker>
                </v-menu>
            </v-col>
            <v-col>
                <buyer-select v-model="buyerId"/>
            </v-col>
        </v-row>
        <v-row>
            <v-col class="d-flex justify-center">
                <v-btn :loading="etiksLoading" @click="etiks">
                    Этикетки
                </v-btn>
            </v-col>
            <v-col class="d-flex justify-center">
                <v-btn :loading="xlsxLoading" @click="xlsx">
                    Скачать список
                </v-btn>
            </v-col>
            <v-col class="d-flex justify-center">
                <v-btn :loading="gtdLoading" @click="clearGtd">
                    Очистить ГТД
                </v-btn>
            </v-col>
            <v-col class="d-flex justify-center">
                <v-btn :loading="transferLoading" @click="transfer">
                    Выгрузить в СБИС
                </v-btn>
            </v-col>
            <v-col class="d-flex justify-center">
                <v-btn :loading="packingListLoading" @click="packingList">
                    Товарные накладные
                </v-btn>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
    import moment from 'moment';
    import BuyerSelect from "./BuyerSelect";

    export default {
        name: "SbisComponent",
        components: {BuyerSelect},
        data() {
            return {
                date: moment().format('Y-MM-DD'),
                datePicker: false,
                buyerId: null,
                xlsxLoading: false,
                etiksLoading: false,
                gtdLoading: false,
                transferLoading: false,
                packingListLoading: false,
            }
        },
        created() {
            this.buyerId = this.$store.getters['AUTH/LOCAL_OPTION']('sbisBuyerId');
        },
        watch: {
            buyerId(val) {
                this.$store.commit('AUTH/SET_LOCAL_OPTION', {sbisBuyerId: val});
            }
        },
        methods: {
            etiks() {
                this.etiksLoading = true;
                this.$store.dispatch('SBIS/ETIKS')
                    .then(() => {
                    })
                    .catch(() => {
                    })
                    .then(() => this.etiksLoading = false);
            },
            xlsx() {
                const {date, buyerId} = this;
                this.xlsxLoading = true;
                this.$store.dispatch('SBIS/XLSX', {date, buyerId})
                    .then(() => {
                    })
                    .catch(() => {
                    })
                    .then(() => this.xlsxLoading = false);
            },
            clearGtd() {
                const {date, buyerId} = this;
                this.gtdLoading = true;
                this.$store.dispatch('SBIS/CLEAR-GTD', {date, buyerId})
                    .then(() => {
                    })
                    .catch(() => {
                    })
                    .then(() => this.gtdLoading = false);
            },
            transfer() {
                const {date, buyerId} = this;
                this.transferLoading = true;
                this.$store.dispatch('SBIS/EXPORT', {date, buyerId})
                    .then(() => {
                    })
                    .catch(() => {
                    })
                    .then(() => this.transferLoading = false);
            },
            packingList() {
                const {date, buyerId} = this;
                this.packingListLoading = true;
                this.$store.dispatch('SBIS/PACKING-LIST', {date, buyerId})
                    .then(() => {
                    })
                    .catch(() => {
                    })
                    .then(() => this.packingListLoading = false);
            }
        }
    }
</script>

<style scoped>

</style>
