<template>
    <v-edit-dialog @open="open" ref="dialog">
        <slot name="cell">{{ value[attribute] }}</slot>
        <template v-if="!disabled" v-slot:input>
            <invoice-status-select @input="save" v-model="editingValue"/>
        </template>
    </v-edit-dialog>
</template>

<script>
import InvoiceStatusSelect from "./InvoiceStatusSelect";

export default {
    name: "InvoiceStatusSelectInline",
    components: {InvoiceStatusSelect},
    props: {
        value: {type: Object, required: true},
        attribute: {type: String, required: true},
        rules: {type: Array, default: () => []},
        disabled: {type: Boolean, default: false},
    },
    data() {
        return {
            editingValue: '',
            }
        },
        methods: {
            open() {
                this.editingValue = this.value[this.attribute];
            },
            save() {

                this.$refs.dialog.isActive = false;
                const validate = this.rules.reduce((res, f) => res === true ? f(this.editingValue) : res, true);
                if (validate !== true) {
                    this.$store.commit('SNACKBAR/ERROR', validate);
                } else {
                    const item = _.cloneDeep(this.value);
                    item[this.attribute] = this.editingValue;
                    this.$emit('save', item);
                }

            },
        }
    }
</script>

<style scoped>

</style>
