<template>
    <v-dialog v-model="isActive">
        <template v-slot:activator="{ on }">
            <v-btn v-on="on"
                   :icon="icon"
                   :x-small="xSmall"
                   :plain="plain"
                   :rounded="rounded"
                   :class="textClass"
            >
                <slot name="button">
                    {{ text }}
                </slot>
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
        text: { type: [String, Number], default: 0 },
        textClass: { type: String, default: '' },
        icon: {
            type: Boolean,
            default: false,
        },
        xSmall: {
            type: Boolean,
            default: false,
        },
        plain: {
            type: Boolean,
            default: false,
        },
        rounded: {
            type: Boolean,
            default: false,
        },
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
