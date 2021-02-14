<template>
    <model-select
        :disabled="disabled"
        :multiple="multiple"
        item-text="NAMEPOST"
        item-value="WHEREISPOSTCODE"
        label="Поставщик"
        model="seller"
        v-model="proxy"
    >
        <template v-slot:prepend>
            <seller-edit-dialog v-model="proxy"/>
        </template>
        <template v-slot:item="{ item }">
            {{ item.NAMEPOST }} / {{ item.INN }}
        </template>
    </model-select>
</template>

<script>
    import utilsMixin from "../mixins/utilsMixin";
    import ModelSelect from "./ModelSelect";
    import Test from "./Test";
    import SellerEditDialog from "./SellerEditDialog";

    export default {
        name: "SellerSelect",
        mixins: [utilsMixin],
        components: {SellerEditDialog, Test, ModelSelect},
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
    }
</script>

<style scoped>

</style>
