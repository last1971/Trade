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
                        single-line
                        v-model="orderLine.QUAN"
                    />
                </v-col>
                <v-col>
                    <v-text-field
                        :rules="rules"
                        single-line
                        v-model="orderLine.priceWithoutVat"
                    />
                </v-col>
                <v-col>
                    <v-text-field
                        :rules="rules"
                        single-line
                        v-model="orderLine.PRICE"
                    />
                </v-col>
                <v-col>
                    <v-text-field
                        :rules="rules"
                        single-line
                        v-model="orderLine.sumWithoutVat"
                    />
                </v-col>
                <v-col>
                    <v-text-field
                        :rules="rules"
                        single-line
                        v-model="orderLine.SUMMAP"
                    />
                </v-col>
            </v-row>
            <v-row>
                <v-col>
                    <v-text-field
                        single-line
                        v-model="orderLine.STRANA"
                    />
                </v-col>
                <v-col>
                    <v-text-field
                        single-line
                        v-model="orderLine.GTD"
                    />
                </v-col>
            </v-row>
            <v-row>
                <v-col col="10">
                    <v-text-field
                        :rules="rules"
                        single-line
                        v-model="orderLine.PRIM"
                    />
                </v-col>
                <v-col col="2">
                    <v-btn :disabled="savePossible" @click="save" fab :loading="loading">
                        <v-icon color="green">mdi-content-save</v-icon>
                    </v-btn>
                </v-col>
            </v-row>
        </v-container>
    </v-card>
</template>

<script>
import GoodSelect from '../GoodSelect'
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
            savePossible: false,
            loading: false,
        }
    },
    methods: {
        save() {
            this.$emit('close');
        }
    }
}
</script>

<style scoped>

</style>
