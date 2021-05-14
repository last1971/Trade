<template>
    <div v-if="restricted" :class="textClass">{{ text }}</div>
    <v-dialog v-model="isActiveProxy" v-else>
        <template v-if="withActivator" v-slot:activator="{ on }">
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
    </v-dialog>
</template>

<script>
import Reserves from "./Reserves";
import FutureReserves from "./FutureStoreReserves";
export default {
    name: "ReservesModal",
    components: {FutureReserves, Reserves},
    props: {
        value: {
            type: Object,
            required: true
        },
        name: {
            type: String,
            default: '...'
        },
        text: {
            type: [String, Number],
            default: 0,
        },
        textClass: {
            type: String,
            default: ''
        },
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
        withActivator: {
            type: Boolean,
            default: true,
        },
        isActive: {
            type: Boolean,
            default: false,
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
        restricted() {
            return !(this.$store.getters['AUTH/HAS_PERMISSION']('reserve.index')
                && this.$store.getters['AUTH/HAS_PERMISSION']('reserve.show'));
        },
    },
    data() {
        return {
            isActiveProxy: this.isActive,
            isNotFutureProxy: this.isNotFuture === null ? true: this.isNotFuture,
        }
    },
    watch: {
        isActive(v) {
            if (v) this.isActiveProxy = v;
        },
        isActiveProxy(v) {
            if (!v) this.$emit('update:isActive', v);
        },
    }
}
</script>

<style scoped>

</style>
