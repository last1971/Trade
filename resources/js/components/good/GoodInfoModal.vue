<template>
    <v-dialog v-model="isActiveProxy" class="p-2">
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
        <good-info :value="value" @close="isActiveProxy = false"/>
    </v-dialog>
</template>

<script>
import GoodInfo from "./GoodInfo";
export default {
    name: "GoodInfoModal",
    components: {GoodInfo},
    props: {
        value: {
            type: Number,
            required: true,
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
        }
    },
    data() {
        return {
            isActiveProxy: this.isActive,
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
