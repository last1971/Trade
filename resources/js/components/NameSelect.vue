<template>
    <model-select
        :dense="dense"
        :disabled="disabled"
        :error-messages="errorMessages"
        :multiple="multiple"
        :with="['category']"
        item-text="NAME"
        item-value="NAMECODE"
        label="Наименовние"
        model="name"
        v-model="proxy"
    >
        <template v-slot:prepend>
            <v-btn @click="add" class="pb-2" icon>
                <v-icon color="green">mdi-plus</v-icon>
            </v-btn>
            <v-dialog max-width="600px" persistent v-model="addName">
                <v-card>
                    <v-card-title>
                        <span class="headline">Новое наименовние</span>
                        <v-spacer/>
                        <v-btn @click="addName = false" icon right>
                            <v-icon color="red">
                                mdi-close
                            </v-icon>
                        </v-btn>
                    </v-card-title>
                    <v-divider></v-divider>
                    <v-card-text>
                        <v-container>
                            <v-row>
                                <v-text-field :rules="[required, length]"
                                              counter="70"
                                              label="Наименовние"
                                              v-model="newName.NAME"
                                              :error-messages="errors['item.NAME']"
                                />
                            </v-row>
                            <v-row>
                                <category-select v-model="newName.CATEGORYCODE"/>
                                <v-btn :disabled="!savePossible" @click="save" class="ml-4 " fab>
                                    <v-icon color="green">mdi-content-save</v-icon>
                                </v-btn>
                            </v-row>
                        </v-container>
                    </v-card-text>
                </v-card>
            </v-dialog>
        </template>
    </model-select>
</template>

<script>
    import utilsMixin from "../mixins/utilsMixin";
    import ModelSelect from "./ModelSelect";
    import CategorySelect from "./CategorySelect";

    export default {
        name: "NameSelect",
        mixins: [utilsMixin],
        components: {CategorySelect, ModelSelect},
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
        data() {
            return {
                addName: false,
                newName: {
                    CATEGORYCODE: null,
                    NAME: null,
                },
                required: (v) => !!v || 'обязательный',
                length: (v) => v.length < 71 || 'нужно сократить',
                errors: {},
            }
        },
        computed: {
            savePossible() {
                return !!this.newName.NAME && this.newName.NAME.length < 71 && this.newName.CATEGORYCODE;
            }
        },
        methods: {
            add() {
                this.newName = {
                    CATEGORYCODE: null,
                    NAME: '',
                };
                this.addName = true;
            },
            save() {
                this.newName.SERIA = this.newName.NAME;
                this.$store.dispatch('NAME/CREATE', {item: this.newName, options: {with: ['category']}})
                    .then((response) => {
                        this.proxy = response.data.NAMECODE
                        this.addName = false
                    })
                    .catch((error) => {
                        if (error.response && error.response.data.errors) {
                            this.errors = _.mapValues(error.response.data.errors, (v) => {
                                return _.isArray(v)
                                    ? v.map((e) => this.$store.getters['ERROR-MESSAGE/GET'](e))
                                    : this.$store.getters['ERROR-MESSAGE/GET'](v);
                            });
                        }
                    })
            }
        }
    }
</script>

<style scoped>

</style>
