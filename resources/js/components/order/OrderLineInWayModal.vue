<template>
    <v-dialog v-model="isActive" v-else>
        <template v-slot:activator="{ on }">
            <v-btn icon v-on="on" :class="textClass">
                {{ text }}
            </v-btn>
        </template>
        <v-card>
            <v-card-title class="headline">
                <v-spacer/>
                <span class="headline">{{ title }}</span>
                <v-spacer/>
                <v-btn @click="isActive = false" icon right>
                    <v-icon color="red">
                        mdi-close
                    </v-icon>
                </v-btn>
            </v-card-title>
            <v-divider></v-divider>
            <order-line-in-way :invoice-line="value" class="mx-2" :top-text="false" />
        </v-card>
    </v-dialog>
</template>

<script>
import OrderLineInWay from "./OrderLineInWay";
export default {
    name: "OrderLineInWayModal",
    components: {OrderLineInWay},
    props: {
        value: { type: Object, required: true },
        name: { type: String, default: '...' },
        text: { type: [String, Number], required: true },
        textClass: { type: String, default: '' },
    },
    computed: {
        title() {
            return this.name + ' в пути'
        },
    },
    data() {
        return {
            isActive: false,
        }
    }
}
</script>

<style scoped>

</style>
