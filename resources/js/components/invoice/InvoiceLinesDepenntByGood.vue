<template>
    <v-card>
        <v-card-title class="headline">
            <v-spacer/>
                <span class="headline">{{ title }}</span>
            <v-spacer/>
        </v-card-title>
        <v-divider></v-divider>
        <invoice-lines-dependent v-model="options" :dependent-value="true" :remove-headers="removeHeaders"/>
    </v-card>
</template>

<script>
import InvoiceLinesDependent from "./InvoiceLinesDependent";
export default {
    name: "InvoiceLinesDepenntByGood",
    components: {InvoiceLinesDependent},
    props: {
        value: {
            type: Number,
            required: true,
        }
    },
    data() {
        return {
            options: {
                with: ['category', 'name', 'good', 'invoice.buyer'],
                aggregateAttributes: ['pickUpsQuantity', 'reservesQuantity'],
                filterAttributes: [
                    'REALPRICE.GOODSCODE',
                ],
                filterOperators: ['='],
                filterValues: [this.value],
                sortBy: ['invoice.DATA'],
                sortDesc: [true],
            },
            removeHeaders: [
                'category.CATEGORY',
                'name.NAME',
                'good.BODY',
                'good.PRODUCER',
                'good.UNIT_I'
            ]
        }
    },
    computed: {
        good() {
            return this.$store.getters['GOOD/GET'](this.value);
        },
        title() {
            return  this.good ? 'Счета для ' + this.good.name.NAME : '';
        },
    }
}
</script>

<style scoped>

</style>
