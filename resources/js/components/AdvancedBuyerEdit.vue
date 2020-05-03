<template>
    <v-dialog max-width="1000px" v-model="proxy">
        <template v-slot:activator="{ on }">
            <v-btn dark icon v-on="on">
                <v-icon color="green">mdi-plus</v-icon>
            </v-btn>
        </template>
        <v-card>
            <v-card-title>
                <span class="headline">{{ title }}</span>
                <v-spacer/>
                <v-btn @click="close" icon right>
                    <v-icon color="red">
                        mdi-close
                    </v-icon>
                </v-btn>
            </v-card-title>
            <v-divider></v-divider>
            <v-card-text>
                <v-container>
                    <v-row>
                        <v-col>
                            <buyer-select :error-messages="errors['item.buyer_id']" v-model="item.buyer_id"/>
                        </v-col>
                        <v-col>
                            <v-text-field :error-messages="errors['item.edo_id']" label="ЭДО" v-model="item.edo_id"/>
                        </v-col>
                    </v-row>
                    <v-row>
                        <v-col>
                            <v-text-field label="Грузополучатель" v-model="item.consignee"></v-text-field>
                        </v-col>
                        <v-col>
                            <v-text-field label="Адрес грузополучателя" v-model="item.consigneeAddress"/>
                        </v-col>
                    </v-row>
                </v-container>
            </v-card-text>

            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn @click="close" outlined>
                    <v-icon color="red" dark>mdi-cancel</v-icon>
                    <span class="ml-2">Отмена</span>
                </v-btn>
                <v-btn @click="save" outlined>
                    <v-icon color="green" dark>mdi-content-save</v-icon>
                    <span class="ml-2">Сохранить</span>
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
    import utilsMixin from "../mixins/utilsMixin";
    import BuyerSelect from "./BuyerSelect";

    export default {
        name: "AdvancedBuyerEdit",
        components: {BuyerSelect},
        props: {
            value: {
                type: Boolean,
                default: false,
            },
            itemValue: {
                type: Number,
                required: true,
            },
        },
        data() {
            return {
                default: {
                    buyer_id: null,
                    edo_id: null,
                    consignee: '',
                    consigneeAddress: '',
                },
                item: {
                    buyer_id: null,
                    edo_id: null,
                    consignee: '',
                    consigneeAddress: '',
                },
                errors: {},
                model: 'ADVANCED-BUYER',
                options: {with: ['buyer']},
            }
        },
        mixins: [utilsMixin],
        computed: {
            title() {
                return this.item.id ? 'Редактировать' : 'Добавить';
            },
        },
        watch: {
            itemValue(val) {
                this.item = val ? this.$store.getters[this.model + '/GET'](val) : _.cloneDeep(this.default);
            }
        },
        methods: {
            close() {
                this.proxy = false;
                this.item = this.itemValue
                    ? this.$store.getters[this.model + '/GET'](this.itemValue)
                    : _.cloneDeep(this.default);
                this.errors = {};
                this.$emit('close');
            },

            save() {
                const {item, options} = this;
                const action = this.item.id
                    ? this.$store.dispatch(this.model + '/UPDATE', {item, options})
                    : this.$store.dispatch(this.model + '/CREATE', {item, options});
                action
                    .then((response) => {
                        this.$emit('saved', response);
                        this.close();
                    })
                    .catch((error) => {
                        if (error.response && error.response.data.errors) {
                            this.errors = _.mapValues(error.response.data.errors, (v) => {
                                return _.isArray(v)
                                    ? v.map((e) => this.$store.getters['ERROR-MESSAGE/GET'](e))
                                    : this.$store.getters['ERROR-MESSAGE/GET'](v);
                            });
                        }
                    });
            },
        }
    }
</script>

<style scoped>

</style>
