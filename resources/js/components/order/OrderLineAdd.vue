<template>
    <v-card>
        <v-card-title class="headline">
            <span class="headline">Строка заказа</span>
            <v-spacer/>
            <v-btn @click="$emit('close')" icon right>
                <v-icon color="red">
                    mdi-close
                </v-icon>
            </v-btn>
        </v-card-title>
        <v-container>
            <v-row>
                <v-col cols="12">
                    <good-select v-model="orderLine.GOODSCODE"/>
                </v-col>
            </v-row>
            <v-row>
                <v-col>
                    <v-text-field
                        :rules="rules"
                        v-model="orderLine.QUAN"
                        label="Количество"
                    />
                </v-col>
                <v-col>
                    <v-text-field
                        :rules="rules"
                        label="Цена без НДС"
                        v-model="orderLine.priceWithoutVat"
                    />
                </v-col>
                <v-col>
                    <v-text-field
                        :rules="rules"
                        label="Цена с НДС"
                        v-model="orderLine.PRICE"
                    />
                </v-col>
                <v-col>
                    <v-text-field
                        :rules="rules"
                        label="Сумма без НДС"
                        v-model="orderLine.sumWithoutVat"
                    />
                </v-col>
                <v-col>
                    <v-text-field
                        :rules="rules"
                        label="Сумма с НДС"
                        v-model="orderLine.SUMMAP"
                    />
                </v-col>
            </v-row>
            <v-row>
                <v-col>
                    <v-text-field
                        label="Страна"
                        v-model="orderLine.STRANA"
                    />
                </v-col>
                <v-col>
                    <v-text-field
                        label="ГТД"
                        v-model="orderLine.GTD"
                    />
                </v-col>
            </v-row>
            <v-row>
                <v-col col="10">
                    <v-text-field
                        :rules="rules"
                        label="Примечание"
                        v-model="orderLine.PRIM"
                    />
                </v-col>
                <v-col col="2">
                    <v-btn :disabled="!savePossible" @click="save" fab :loading="loading">
                        <v-icon color="green">mdi-content-save</v-icon>
                    </v-btn>
                </v-col>
            </v-row>
        </v-container>
    </v-card>
</template>

<script>
import GoodSelect from '../good/GoodSelect'
export default {
    name: "OrderLineAdd",
    components: { GoodSelect },
    props: {
        order: { type: Object, required: true },
    },
    data() {
        return {
            orderLine: {},
            rules: [],
            loading: false,
        }
    },
    computed: {
        savePossible() {
            return orderLine.GOODSCODE && orderLine.QUAN && orderLine.PRICE && orderLine.SUMMAP;
        }
    },
    methods: {
        save() {
            this.$emit('close');
        },
    },
    watch: {
        orderLine: {
            deep: true,
            handler: (val, oldVal) => {
                console.log(val, oldVal);
            }
        },
    }
}
</script>

<style scoped>

</style>
