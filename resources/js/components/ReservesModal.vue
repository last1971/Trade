<template>
    <div v-if="restricted" :class="textClass">{{ text }}</div>
    <v-dialog v-model="isActive" v-else>
        <template v-slot:activator="{ on }">
            <v-btn icon v-on="on" :class="textClass">
                {{ text }}
            </v-btn>
        </template>
        <v-card>
            <v-card-title class="headline">
                <v-btn @click="isNotFuture = !isNotFuture" icon left>
                    <v-icon v-if="isNotFuture">
                        mdi-chevron-right
                    </v-icon>
                    <v-icon v-else>
                        mdi-chevron-left
                    </v-icon>
                </v-btn>
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
            <reserves v-if="isNotFuture" :value="value" :top-text="false" class="mx-2"/>
            <future-reserves v-else :value="value" :top-text="false" class="mx-2"/>
        </v-card>
    </v-dialog>
</template>

<script>
import Reserves from "./Reserves";
import FutureReserves from "./FutureStoreReserves";
export default {
    name: "ReservesModal",
    components: {FutureReserves, Reserves},
    props: {
        value: { type: Object, required: true },
        name: { type: String, default: '...' },
        text: { type: [String, Number], required: true },
        textClass: { type: String, default: '' },
    },
    computed: {
        title() {
            return (this.isNotFuture ? 'Резервы ' : 'Будующие резервы ') + 'для ' + this.name
        },
        restricted() {
            return !(this.$store.getters['AUTH/HAS_PERMISSION']('reserve.index')
                && this.$store.getters['AUTH/HAS_PERMISSION']('reserve.show'));
        },
    },
    data() {
        return {
            isActive: false,
            isNotFuture: true,
        }
    }
}
</script>

<style scoped>

</style>
