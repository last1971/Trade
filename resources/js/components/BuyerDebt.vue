<template>
    <v-container>
        <v-card class="mx-auto mt-4" max-width="640">
            <v-card-title>Долги покупателя</v-card-title>
            <v-card-text>
                <buyer-select v-model="buyer"/>
                <div class="d-flex align-center">
                    <date-picker v-model="from" label="Счета не ранее"/>
                    <v-btn v-if="from" icon small class="ml-2" title="Сбросить дату" @click="from = null">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </div>
            </v-card-text>
            <v-card-actions>
                <v-spacer/>
                <v-btn
                    color="success"
                    :disabled="!buyer"
                    :loading="saving"
                    @click="save"
                >
                    <v-icon left>mdi-microsoft-excel</v-icon>
                    Выгрузить Excel
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-container>
</template>

<script>
    import BuyerSelect from "./BuyerSelect";
    import DatePicker from "./DatePicker";
    import moment from "moment";
    import createLocalStorageSync from "../helpers/localStorage";

    const filterStorage = createLocalStorageSync('buyer_debt_filter');

    export default {
        name: "BuyerDebt",
        components: {BuyerSelect, DatePicker},
        data: () => ({
            buyer: null,
            from: null,
            saving: false,
        }),
        created() {
            // Восстанавливаем последний выбранный покупателя и дату.
            const saved = filterStorage.get({});
            this.buyer = saved.buyer || null;
            this.from = saved.from || null;
        },
        watch: {
            buyer() {
                this.persistFilter();
            },
            from() {
                this.persistFilter();
            },
        },
        computed: {
            filename() {
                const item = this.$store.getters['BUYER/GET'](this.buyer);
                const name = ((item && item.SHORTNAME) || this.buyer || '').toString().trim();
                const today = moment().format('DD.MM.YYYY');
                const from = this.from ? ' с ' + moment(this.from).format('DD.MM.YYYY') : '';
                // Чистим символы, недопустимые в имени файла.
                return ('Долги ' + name + from + ' по ' + today + '.xlsx')
                    .replace(/[\\/:*?"<>|]/g, ' ')
                    .replace(/\s+/g, ' ')
                    .trim();
            }
        },
        methods: {
            persistFilter() {
                filterStorage.set({buyer: this.buyer, from: this.from});
            },
            save() {
                if (!this.buyer) return;
                this.saving = true;
                this.$store.dispatch('BUYER-DEBT/SAVE', {
                    buyer: this.buyer,
                    from: this.from || undefined,
                    filename: this.filename,
                })
                    .catch(() => {})
                    .then(() => this.saving = false);
            }
        }
    }
</script>

<style scoped>

</style>
