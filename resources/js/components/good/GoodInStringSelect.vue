<template>
    <v-edit-dialog ref="dialog" :large="!disabled" @open="open" @save="save">
        <template v-slot:default>
            <good-in-string v-if="good(value)" :value="good(value)" />
            <v-btn v-else plain>П Р И В Я З А Т Ь</v-btn>
        </template>
        <template v-if="!disabled" v-slot:input>
            <good-select v-model="goodId"/>
        </template>
    </v-edit-dialog>
</template>

<script>
import GoodInString from "./GoodInString";
import GoodSelect from "./GoodSelect";
export default {
    name: "GoodInStringSelect",
    components: {GoodSelect, GoodInString},
    props: {
        value: {
            required: true,
            validator: prop => typeof prop === 'number' || prop === null,
        },
        disabled: {
            type: Boolean,
            default: false
        },
    },
    data() {
        return {
            goodId: null,
        }
    },
    mounted() {
        const el = this.$refs['dialog'].$el;
        this.$nextTick(() => {
            $(el).prev().children().first().css('display','flex')
        });
    },
    methods: {
        good(id) {
            return  this.$store.getters['GOOD/GET'](id);
        },
        open() {
            this.goodId = this.value;
        },
        save() {
            this.$emit('input', this.goodId);
        }
    }
}
</script>

<style scoped>

</style>
