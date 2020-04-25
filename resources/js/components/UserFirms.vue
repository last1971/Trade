<template>
    <firm-select :multiple="true" v-model="proxy"/>
</template>

<script>
    import FirmSelect from "./FirmSelect";

    export default {
        name: "UserFirms",
        components: {FirmSelect},
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
        },
        computed: {
            proxy: {
                get() {
                    return this.value.map((v) => v.firm.FIRM_ID)
                },
                set(val) {
                    this.$emit(
                        'input',
                        this.$store.getters['FIRM/ALL']
                            .filter((v) => val.indexOf(v.FIRM_ID) >= 0)
                            .map((v) => {
                                return {firm: v}
                            })
                    );
                }
            }
        },
    }
</script>

<style scoped>

</style>
