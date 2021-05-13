<template>
    <div>
    <v-speed-dial :open-on-hover="true">
        <template v-slot:activator>
            <v-btn x-small plain rounded>
                <v-badge :color="color(quantity(good))" :content="quantity(good)" inline>
                    есть
                </v-badge>
            </v-btn>
        </template>
        <v-btn x-small @click.stop="test" rounded>
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
    </div>
</template>

<script>
import TransferOutLinesModal from "../transferOut/TransferOutLinesModal";
export default {
    name: "GoodQuantity",
    components: {TransferOutLinesModal},
    props: {
        good: {
            type: Object,
        },
    },
    data() {
        return {
            transferOutLinesIsActive: false,
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
