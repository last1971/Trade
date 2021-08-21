<template>
    <v-row>
        <v-col cols="12" sm="3">
            <v-text-field :error-messages="errors['item.PRICEROZN']"
                          :reverse="true"
                          prefix="₽"
                          suffix="от 1 шт."
                          type="number"
                          v-model="model.PRICEROZN"
                          :disabled="notEditable"
            />
        </v-col>
        <v-col cols="12" sm="2">
            <v-text-field :error-messages="errors['item.QUANMOPT']"
                          :reverse="true" prefix="шт."
                          suffix="от"
                          v-model="model.QUANMOPT"
                          :disabled="notEditable"
            />
        </v-col>
        <v-col cols="12" sm="2">
            <v-text-field :error-messages="errors['item.PRICEMOPT']"
                          :reverse="true"
                          prefix="₽"
                          v-model="model.PRICEMOPT"
                          :disabled="notEditable"
            />
        </v-col>
        <v-col cols="12" sm="2">
            <v-text-field :error-messages="errors['item.QUANOPT']"
                          :reverse="true"
                          prefix="шт."
                          suffix="от"
                          v-model="model.QUANOPT"
                          :disabled="notEditable"
            />
        </v-col>
        <v-col cols="12" sm="2">
            <v-text-field :error-messages="errors['item.PRICEOPT']"
                          :reverse="true"
                          prefix="₽"
                          v-model="model.PRICEOPT"
                          :disabled="notEditable"
            />
        </v-col>
        <v-col cols="12" sm="1">
            <v-btn :disabled="savePossible || notEditable" @click="save" fab :loading="loading">
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
        computed: {
            notEditable() {
                return !this.$store.getters['AUTH/HAS_PERMISSION']('retail-price.update');
            }
        }
    }
</script>

<style scoped>

</style>
