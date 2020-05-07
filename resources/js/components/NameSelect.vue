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
                                />
                            </v-row>
                            <v-row>
                                <category-select v-model="newName.CATEGORYCODE"/>
                                <v-btn :disabled="!savePossible" class="ml-4 " fab>
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
            }
        }
    }
</script>

<style scoped>

</style>
