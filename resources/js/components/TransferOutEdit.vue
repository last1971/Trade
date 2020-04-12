<template>
    <v-form class="mx-2">
        <v-row>
            <v-col cols="12" sm="auto">
                <v-menu
                    :close-on-content-click="false"
                    :nudge-right="40"
                    min-width="290px"
                    offset-y
                    transition="scale-transition"
                    v-model="datePicker"
                >
                    <template v-slot:activator="{ on }">
                        <v-text-field
                            :disabled="notEditable"
                            :value="model.DATA | formatDate"
                            label="Дата"
                            prepend-icon="mdi-calendar-edit"
                            readonly
                            v-on="on"
                        />
                    </template>
                    <v-date-picker @input="datePicker = false" v-model="model.DATA"></v-date-picker>
                </v-menu>
            </v-col>
            <v-col cols="12" sm="auto">
                <v-text-field :disabled="notEditable"
                              :rules="[rules.required, rules.isInteger]"
                              label="Номер"
                              v-model="model.NSF"
                />
            </v-col>
            <v-col cols="12" sm="auto">
                <buyer-select :disabled="notEditable" v-model="model.POKUPATCODE"/>
            </v-col>
            <v-col cols="12" sm="auto">
                <firm-select :disabled="notEditable" v-model="model.FIRM_ID"/>
            </v-col>
            <v-col cols="12" sm="auto">
                <v-text-field :disabled="notEditable" label="Примечание" v-model="model.PRIM"/>
            </v-col>
            <v-col class="d-flex justify-center align-center" cols="12" sm="auto">
                <router-link :to="{ name: 'invoice', params: { id: model.SCODE } }"
                             class="title"
                >
                    Счет № {{ model.invoice.NS }} от {{ model.invoice.DATA | formatDate }}
                </router-link>
            </v-col>
            <v-col cols="12" sm="auto">
                <v-btn :block="!$vuetify.breakpoint.smAndUp"
                       :disabled="savePossible"
                       :fab="$vuetify.breakpoint.smAndUp"
                       :loading="loading"
                       @click="save"
                       class="mt-2"
                >
                    <v-icon v-if="$vuetify.breakpoint.smAndUp">mdi-content-save</v-icon>
                    <span v-else>Сохранить</span>
                </v-btn>
            </v-col>
        </v-row>
    </v-form>
</template>

<script>
    import utilsMixin from "../mixins/utilsMixin";
    import BuyerSelect from "./BuyerSelect";
    import FirmSelect from "./FirmSelect";

    export default {
        name: "TransferOutEdit",
        mixins: [utilsMixin],
        components: {BuyerSelect, FirmSelect},
        props: {
            value: {
                type: Object,
                required: true,
            }
        },
        data() {
            return {
                model: {},
                datePicker: false,
                loading: false,
            }
        },
        computed: {
            notEditable() {
                return true;
            },
            savePossible() {
                const a = _.pick(_.omit(this.value, ['DATA']), this.fillable);
                const b = _.pick(_.omit(this.model, ['DATA']), this.fillable);
                const d = this.value.DATA ? this.value.DATA.substr(0, 10) : undefined;
                return _.isEqual(a, b) && this.model.DATA === d;
            },
            options() {
                return this.$store.getters['USER/LOCAL_OPTION']('TRANSFER-OUT');
            },
            fillable() {
                return this.$store.getters['TRANSFER-OUT/FILLABLE'];
            }
        },
        created() {
            this.initialModel();
        },
        watch: {
            value() {
                this.initialModel();
            }
        },
        methods: {
            initialModel() {
                this.model = _.cloneDeep(this.value);
                if (this.model.DATA) {
                    this.model.DATA = this.model.DATA.substr(0, 10);
                }
            },
            save() {
                this.loading = true;
                this.$store.dispatch(
                    'TRANSFER-OUT/UPDATE',
                    {item: this.model, options: this.options}
                )
                    .then(() => {
                    })
                    .catch(() => {
                    })
                    .then(() => {
                        this.loading = false;
                        this.model();
                    });
            }
        }
    }
</script>

<style scoped>

</style>
