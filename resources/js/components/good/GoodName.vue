<template>
    <div>
        <div class="d-flex align-center">
            <span v-if="notAuth">{{ value.name.NAME }}</span>
            <router-link :to="{ name: 'good', params: { id: value.GOODSCODE }}" v-else>
                {{ value.name.NAME }}
            </router-link>
            <v-icon small class="ml-1" title="Найти на главной" @click="searchAtHome">mdi-magnify</v-icon>
        </div>
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
        },
        methods: {
            // Иконка: переход на «Домой» с подстановкой названия в поиск и количества строки (счёт/УПД).
            searchAtHome() {
                const query = {search: this.value.name.NAME};
                if (this.value.QUAN != null) {
                    query.quantity = this.value.QUAN;
                }
                this.$router.push({name: 'home', query});
            }
        }
    }
</script>

<style scoped>

</style>
