<template>
    <v-card class="m-1">
        <v-card-subtitle class="text-center">
            {{ seller.name }}
        </v-card-subtitle>
        <v-card-text>
            <v-row>
                <v-col v-if="seller.isApi">
                    <v-switch v-model="value.isApi"
                              label="АПИ"
                              hide-details
                              dense
                              :loading="seller.loading.isApi"
                    />
                </v-col>
                <v-col v-if="seller.isFile">
                    <v-switch v-model="value.isFile"
                              label="Файл"
                              hide-details
                              dense
                              :loading="seller.loading.isFile"
                    />
                </v-col>
            </v-row>
        </v-card-text>
    </v-card>
</template>

<script>
export default {
    name: "SellerApiFileSelect",
    props: {
        value: {
            type: Object,
            required: true,
        },
        seller: {
            type: Object,
            required: true,
        }
    }
}
</script>

<style scoped>

</style>
