<template>
    <v-row>
        <v-col cols="12" sm="2">
            <v-text-field :error-messages="errors['item.BOUND_QUAN_SHOP']"
                          :reverse="true"
                          prefix="шт."
                          suffix="Маг."
                          v-model="model.BOUND_QUAN_SHOP"
                          :disabled="notEditable"
            />
        </v-col>
        <v-col cols="12" sm="2">
            <v-text-field :error-messages="errors['item.QUAN_TO_ZAKAZ_SHOP']"
                          :reverse="true" prefix="шт."
                          suffix="зак."
                          v-model="model.QUAN_TO_ZAKAZ_SHOP"
                          :disabled="notEditable"
            />
        </v-col>
        <v-col cols="12" sm="2">
            <v-text-field :error-messages="errors['item.BOUND_QUAN_SKLAD']"
                          :reverse="true"
                          prefix="шт."
                          suffix="Скл."
                          v-model="model.BOUND_QUAN_SKLAD"
                          :disabled="notEditable"
            />
        </v-col>
        <v-col cols="12" sm="2">
            <v-text-field :error-messages="errors['item.QUAN_TO_ZAKAZ_SKLAD']"
                          :reverse="true" prefix="шт."
                          suffix="зак."
                          v-model="model.QUAN_TO_ZAKAZ_SKLAD"
                          :disabled="notEditable"
            />
        </v-col>
        <v-col cols="12" sm="3">
            <div v-if="notEditable" class="mt-5 body-1">C E N S O R E D</div>
            <div v-else class="mt-5 body-1">{{model.DATA | formatDate}} изменил: {{model.USERNAME}}</div>
        </v-col>
        <v-col cols="12" sm="1">
            <v-btn :disabled="savePossible || notEditable" :loading="loading" @click="save" fab>
                <v-icon color="green">mdi-content-save</v-icon>
            </v-btn>
        </v-col>
    </v-row>
</template>

<script>
    import editMixin from "../../mixins/editMixin";

    export default {
        name: "OrderStepEdit",
        mixins: [editMixin],
        data() {
            return {
                MODEL: 'ORDER-STEP',
                dependent: true,
            }
        },
        computed: {
            notEditable() {
                return !this.$store.getters['AUTH/HAS_PERMISSION']('retail-price.update');
            },
        },
    }
</script>

<style scoped>

</style>
