<template>
    <v-edit-dialog @open="open" @save="save" ref="dialog">
        <slot name="cell">{{ value[attribute] }}</slot>
        <template v-if="!disabled" v-slot:input>
            <slot :editingv.sync="editingValue" name="input">
                <v-text-field
                    :rules="rules"
                    single-line
                    v-model="editingValue"
                />
            </slot>
            <v-row v-if="additionalText">
                <v-col>
                    <small class="grey--text">{{ additionalText }}</small>
                </v-col>
            </v-row>
        </template>
    </v-edit-dialog>
</template>

<script>
    export default {
        name: "EditField",
        props: {
            value: {type: Object, required: true},
            attribute: {type: String, required: true},
            rules: {type: Array, default: () => []},
            additionalText: {type: String, required: false},
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
