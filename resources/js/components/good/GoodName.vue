<template>
    <div>
        <div v-if="notAuth">{{ value.name.NAME }}</div>
        <router-link :to="{ name: 'good', params: { id: value.GOODSCODE }}" v-else>
            {{ value.name.NAME }}
        </router-link>
        <div class="font-italic" style="font-size: 10px" v-if="remark">{{ remark }}</div>
    </div>
</template>

<script>
    export default {
        name: "GoodName",
        props: {
            value: {
                type: Object,
                required: true,
            },
            prim: {
                type: [String, Boolean],
                default: true,
            }
        },
        computed: {
            notAuth() {
                return !this.$store.getters['AUTH/HAS_PERMISSION']('good.show');
            },
            remark() {
                return this.prim === true ? this.value.PRIM.trim() + ' / ' + this.value.DESCRIPTION.trim() : this.prim;
            }
        }
    }
</script>

<style scoped>

</style>
