<template>
    <div>
        <v-speed-dial :open-on-hover="true">
            <template v-slot:activator>
                <v-btn x-small plain rounded>
                    <v-badge :color="color(quantity(good))" :content="quantity(good)" inline>
                        ест
                    </v-badge>
                </v-btn>
            </template>
            <v-btn x-small @click.stop="storeLinesIsActive = true" rounded>
                Куплено {{ good.storeLinesQuantity }}
            </v-btn>
            <v-btn x-small @click.stop="transferOutLinesIsActive = true" rounded>
                Продано {{ good.transferOutLinesQuantity }}
            </v-btn>
        </v-speed-dial>
        <transfer-out-lines-modal
            :good="good"
            :with-activator="false"
            :is-active.sync="transferOutLinesIsActive"
        />
        <store-lines-modal
            :good="good"
            :with-activator="false"
            :is-active.sync="storeLinesIsActive"
        />
    </div>
</template>

<script>
import TransferOutLinesModal from "../transferOut/TransferOutLinesModal";
import StoreLinesModal from "../StoreLinesModal";
export default {
    name: "GoodQuantity",
    components: {StoreLinesModal, TransferOutLinesModal},
    props: {
        good: {
            type: Object,
        },
    },
    data() {
        return {
            transferOutLinesIsActive: false,
            storeLinesIsActive: false,
        }
    },
    methods: {
        quantity(item) {
            return (
                (item.warehouse ? item.warehouse.QUAN : 0) + (item.retailStore ? item.retailStore.QUAN : 0)
            ).toString();
        },
        color(v) {
            return v !== '0' ? 'green' : 'red';
        },
        test() {

        },
    }
}
</script>

<style scoped>

</style>
