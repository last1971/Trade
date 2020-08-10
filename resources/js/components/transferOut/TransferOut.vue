<template>
    <model :aggregate-attributes="['transferOutLinesCount', 'transferOutLinesSum']"
           :lines="TransferOutLines"
           :with="['buyer', 'employee', 'firm', 'invoice']"
           date="DATA"
           name="Исх.УПД"
           number="NSF"
           value="transfer-out"
    />
</template>

<script>
    import Model from '../Model';
    import TransferOutLines from "./TransferOutLines";

    export default {
        name: "TransferOut",
        components: {Model},
        computed: {
            TransferOutLines() {
                return TransferOutLines;
            }
        }
    }
</script>

<style scoped>

</style>
