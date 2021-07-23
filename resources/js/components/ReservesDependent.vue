<template>
    <v-card>
        <v-card-title class="headline">
            <v-btn v-if="isNotFuture === null" @click="isNotFutureProxy = !isNotFutureProxy" icon left>
                <v-icon v-if="isNotFutureProxy">
                    mdi-chevron-right
                </v-icon>
                <v-icon v-else>
                    mdi-chevron-left
                </v-icon>
            </v-btn>
            <v-spacer/>
            <span class="headline">{{ title }}</span>
            <v-spacer/>
            <v-btn @click="isActiveProxy = false" icon right>
                <v-icon color="red">
                    mdi-close
                </v-icon>
            </v-btn>
        </v-card-title>
        <v-divider></v-divider>
        <reserves v-if="isNotFutureProxy" :value="value" :top-text="false" class="mx-2"/>
        <future-reserves v-else :value="value" :top-text="false" class="mx-2"/>
    </v-card>
</template>

<script>
import FutureReserves from "./FutureStoreReserves";
import Reserves from "./Reserves";
export default {
    name: "ReservesDependent",
    components: {Reserves, FutureReserves},
    props: {
        value: {
            type: Object,
            required: true
        },
        name: {
            type: String,
            default: '...'
        },
        isNotFuture: {
            type: Boolean,
            default: null,
        }

    },
    computed: {
        title() {
            return (this.isNotFutureProxy ? 'Резервы ' : 'Будующие резервы ') + 'для ' + this.name
        },
    },
    data() {
        return {
            isNotFutureProxy: this.isNotFuture === null ? true: this.isNotFuture,
        }
    },
}
</script>

<style scoped>

</style>
