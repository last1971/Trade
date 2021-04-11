<template>
    <model-select
        :aggregate-attributes="aggregateAttributes"
        :dense="dense"
        :disabled="disabled"
        :get-value="getValue"
        :key="reload"
        :with="with_"
        item-text="name.NAME"
        item-value="GOODSCODE"
        :label="label"
        model="good"
        v-model="proxy"
        :new-search="newSearch"
        :smart-name="smartName"
        @clearSearchName="$emit('clearSearchName')"
    >
        <template v-slot:item="{ item, maxLength }">
            <good-in-string v-model="item" :rows="rows"/>
        </template>
        <template v-slot:selection="{ item }">
            <good-in-string v-model="item" />
        </template>
        <template v-slot:prepend>
            <v-dialog v-model="goodEdit">
                <template v-slot:activator="{ on }">
                    <v-btn icon v-on="on">
                        <v-icon color="green">mdi-content-save-edit</v-icon>
                    </v-btn>
                </template>
                <v-card>
                    <v-card-title class="headline">
                        <span class="headline">Товар</span>
                        <v-spacer/>
                        <v-btn @click="goodEdit = false" icon right>
                            <v-icon color="red">
                                mdi-close
                            </v-icon>
                        </v-btn>
                    </v-card-title>
                    <good-edit @input="goodEdit = false" v-model="good"/>
                </v-card>
            </v-dialog>
        </template>
    </model-select>
</template>

<script>
    import utilsMixin from "../../mixins/utilsMixin";
    import ModelSelect from "../ModelSelect";
    import GoodInString from "./GoodInString";
    import GoodEdit from "./GoodEdit";

    export default {
        name: "GoodSelect",
        mixins: [utilsMixin],
        components: {GoodEdit, GoodInString, ModelSelect},
        props: {
            value: {type: [Array, Number, String]},
            disabled: {type: Boolean, default: false},
            dense: {type: Boolean, default: false},
            newSearch: {type: String, default: ''},
            smartName: {type: Boolean, default: false}
        },
        data() {
            return {
                with_: ['name', 'retailStore', 'warehouse', 'orderStep', 'category', 'retailPrice'],
                aggregateAttributes: [
                    'reservesQuantity',
                    'invoiceLinesQuantityTransit',
                    'reservesQuantityTransit',
                    'pickUpsTransitQuantity',
                    'retailOrderLinesNeedQuantity',
                    'orderLinesTransitQuantity',
                    'shopLinesTransitQuantity',
                    'storeLinesTransitQuantity',
                ],
                goodEdit: false,
                getValue: false,
                reload: 0,
                label: this.dense ? null : 'Товар'
            }
        },
        computed: {
            proxy: {
                get() {
                    return this.value
                },
                set(val) {
                    this.$emit('input', val)
                }
            },
            MODEL() {
                return _.toUpper(this.model);
            },
            good: {
                get() {
                    const id = this.proxy || 0;
                    return this.$store.getters['GOOD/GET'](id);
                },
                set(val) {
                    this.$emit('input', val.GOODSCODE);
                    this.getValue = !this.getValue;
                    this.reload += 1;
                }
            },
            rows() {
                return this.$vuetify.breakpoint.name === 'xl' ? 1 : 2;
            }
        },
        methods: {
            padEnd(v, l) {
                return _.padEnd(v, l, '.');
            },
        }
    }
</script>

<style scoped>

</style>
