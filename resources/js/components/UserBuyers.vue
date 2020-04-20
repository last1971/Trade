<template>
    <buyer-select :dense="dense" :multiple="true" v-model="proxy"/>
</template>

<script>
    import BuyerSelect from "./BuyerSelect";

    export default {
        name: "UserBuyers",
        components: {BuyerSelect},
        props: {
            value: {
                type: [Array, Number]
            },
            multiple: {
                type: Boolean,
                default: false
            },
            disabled: {
                type: Boolean,
                default: false,
            },
            dense: {
                type: Boolean,
                default: false,
            },
        },
        computed: {
            proxy: {
                get() {
                    return this.value.map((v) => v.buyer.POKUPATCODE)
                },
                set(val) {
                    this.$emit(
                        'input',
                        this.$store.getters['BUYER/ALL']
                            .filter((v) => val.indexOf(v.POKUPATCODE) >= 0)
                            .map((v) => {
                                return {buyer: v}
                            })
                    );
                }
            }
        },
    }
</script>

<style scoped>

</style>
