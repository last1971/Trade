<template>
    <model-select
        v-model="proxy"
        label="Заказы"
        model="seller-order"
        item-value="id"
        :filter-attributes="['seller_id']"
        :filter-operators="['=']"
        :filter-values="[sellerId]"
    >
        <template v-slot:item>
            {{ item.date | formatDate }}
            <span v-if="item.number">
                № {{ item.number }}
            </span>
            { {{ item.remark }} }
            {{ item.amount | formatRub }}
            {{ item.closed ? 'открыт' : 'закрыт' }}
        </template>
    </model-select>

</template>

<script>
import ModelSelect from "../ModelSelect";
import utilsMixin from "../../mixins/utilsMixin";
export default {
    name: "SellerOrderSelect",
    components: {ModelSelect},
    mixins:[utilsMixin],
    props: {
        value: {
            validate: (v) => typeof v === 'number' || v === null,
        },
        sellerId: {
            validate: (v) => typeof v === 'number' || v === null,
            default: null,
        }
    }
}
</script>

<style scoped>

</style>
