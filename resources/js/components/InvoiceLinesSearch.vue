<template>
    <invoice-lines-dependent v-model="options">
        <template v-slot:top>
            <v-form class="mx-2">
                <v-row>
                    <v-text-field class="mx-2" label="Назвние" v-model="name"/>
                    <v-select :items="statuses"
                              label="Статус счета"
                              v-model="status"
                    />
                    <v-text-field class="mx-2"/>
                </v-row>
            </v-form>
        </template>
    </invoice-lines-dependent>
</template>

<script>
    import InvoiceLinesDependent from "./InvoiceLinesDependent";
    import InvoiceStatusSelect from "./InvoiceStatusSelect";

    export default {
        name: "InvoiceLinesSearch",
        components: {InvoiceStatusSelect, InvoiceLinesDependent},
        data() {
            return {
                options: {
                    with: ['category', 'good', 'name', 'invoice'],
                    aggregateAttributes: [
                        'reservesQuantity', 'pickUpsQuantity', 'transferOutLinesQuantity'
                    ],
                    filterAttributes: [],
                    filterOperators: [],
                    filterValues: [],
                },
                name: '',
                status: '',
                statuses: [
                    {text: 'Все', value: ''},
                    {text: 'В работе', value: '0,1,2,3,4'},
                    {text: 'Закрыт', value: '5'},
                    {text: 'Без корзины', value: '0,1,2,3,4,5'},
                ]
            }
        },
        watch: {
            name: _.debounce(function () {
                this.search('name.NAME', 'CONTAIN', this.name);
            }, 500),
            status: _.debounce(function () {
                this.search('invoice.STATUS', 'IN', this.status);
            }, 500)
        },
        methods: {
            search(attr, oper, val) {
                let index = _.indexOf(this.options.filterAttributes, attr);
                if (index < 0) {
                    if (!_.isEmpty(val)) {
                        this.options.filterAttributes.push(attr);
                        this.options.filterOperators.push(oper);
                        this.options.filterValues.push(val);
                    }
                } else {
                    if (_.isEmpty(val)) {
                        this.options.filterAttributes.splice(index, 1)
                        this.options.filterOperators.splice(index, 1);
                        this.options.filterValues.splice(index, 1);
                    } else {
                        this.options.filterOperators.splice(index, 1, oper);
                        this.options.filterValues.splice(index, 1, val);
                    }
                }
            }
        }
    }
</script>

<style scoped>

</style>
