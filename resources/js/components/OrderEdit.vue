<template>
    <v-form class="mx-2">
        <v-row>
            <v-col cols="12" sm="auto">
                <v-text-field :disabled="true"
                              label="Наш номер"
                              v-model="model.NZAKAZ"
                />
            </v-col>
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
                            :value="model.INVOICE_DATA | formatDate"
                            label="Дата"
                            prepend-icon="mdi-calendar-edit"
                            readonly
                            v-on="on"
                        />
                    </template>
                    <v-date-picker @input="datePicker = false" v-model="model.INVOICE_DATA"></v-date-picker>
                </v-menu>
            </v-col>
            <v-col cols="12" sm="auto">
                <v-text-field :disabled="notEditable"
                              :rules="[rules.required]"
                              label="Номер"
                              v-model="model.INVOICE_NUM"
                />
            </v-col>
            <v-col cols="12" sm="auto">
                <buyer-select :disabled="notEditable" v-model="model.POKUPATCODE"/>
            </v-col>
            <v-col cols="12" sm="auto">
                <firm-select :disabled="notEditable" v-model="model.FIRM_ID"/>
            </v-col>
            <v-col cols="12" sm="auto">
                <invoice-status-select v-model="model.STATUS"/>
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
        <v-row>
            <v-col cols="12" sm="8">
                <v-text-field label="Примечание" v-model="model.PRIM"/>
            </v-col>
        </v-row>
    </v-form>
</template>

<script>
    import SellerSelect from "./SellerSelect";
    import editMixin from "../mixins/editMixin";
    import utilsMixin from "../mixins/utilsMixin";

    export default {
        name: "OrderEdit",
        components: {SellerSelect},
        mixins: [editMixin, utilsMixin],
        data() {
            return {
                MODEL: 'ORDER'
            }
        },
        computed: {
            notEditable() {
                return this.model.INSUM > 0;
            },
            savePossible() {
                /*const a = _.pick(_.omit(this.value, ['DATA']), this.fillable);
                const b = _.pick(_.omit(this.model, ['DATA']), this.fillable);
                const d = this.value.DATA ? this.value.DATA.substr(0, 10) : undefined;
                return _.isEqual(a, b) && this.model.DATA === d;

                 */
                return true;
            },
        },
        methods: {
            initialModel() {
                this.model = _.cloneDeep(this.value);
                this.model.INVOICE_DATA = this.model.INVOICE_DATA.substr(0, 10);
            },
        }
    }
</script>

<style scoped>

</style>
