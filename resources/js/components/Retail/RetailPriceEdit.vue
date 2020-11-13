<template>
    <v-row>
        <v-col cols="12" sm="3">
            <v-text-field :error-messages="errors['item.PRICEROZN']"
                          :reverse="true"
                          prefix="₽"
                          suffix="от 1 шт."
                          type="number"
                          v-model="model.PRICEROZN"
            />
        </v-col>
        <v-col cols="12" sm="2">
            <v-text-field :error-messages="errors['item.QUANMOPT']"
                          :reverse="true" prefix="шт."
                          suffix="от"
                          v-model="model.QUANMOPT"
            />
        </v-col>
        <v-col cols="12" sm="2">
            <v-text-field :error-messages="errors['item.PRICEMOPT']"
                          :reverse="true"
                          prefix="₽"
                          v-model="model.PRICEMOPT"
            />
        </v-col>
        <v-col cols="12" sm="2">
            <v-text-field :error-messages="errors['item.QUANOPT']"
                          :reverse="true"
                          prefix="шт."
                          suffix="от"
                          v-model="model.QUANOPT"
            />
        </v-col>
        <v-col cols="12" sm="2">
            <v-text-field :error-messages="errors['item.PRICEOPT']"
                          :reverse="true"
                          prefix="₽"
                          v-model="model.PRICEOPT"
            />
        </v-col>
        <v-col cols="12" sm="1">
            <v-btn :disabled="savePossible" @click="save" fab :loading="loading">
                <v-icon color="green">mdi-content-save</v-icon>
            </v-btn>
        </v-col>
    </v-row>
</template>

<script>
    import editMixin from "../../mixins/editMixin";

    export default {
        name: "RetailPriceEdit",
        mixins: [editMixin],
        data() {
            return {
                MODEL: 'RETAIL-PRICE',
                dependent: true,
            }
        },
    }
</script>

<style scoped>

</style>
