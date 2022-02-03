<template>
    <model-select
        :disabled="disabled"
        :multiple="multiple"
        item-text="SHORTNAME"
        item-value="POKUPATCODE"
        label="Покупатель"
        model="buyer"
        v-model="proxy"
        :dense="dense"
        :error-messages="errorMessages"
    >
        <template v-slot:item="{ item }">
            {{ card(item) }}
        </template>

    </model-select>
</template>

<script>
    import ModelSelect from "./ModelSelect";
    import utilsMixin from "../mixins/utilsMixin";

    export default {
        name: "BuyerSelect",
        mixins: [utilsMixin],
        components: {ModelSelect},
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
            errorMessages: {
                type: Array,
                default: () => [],
            }
        },
        methods: {
            card(item) {
                let res = item.SHORTNAME;
                if (item.INN) {
                    const index = item.INN.indexOf('/');
                    res += index >=0 ? ' / ' + item.INN.substring(0, index) : item.INN;
                }
                return res;
            }
        }
    }
</script>

<style scoped>

</style>
