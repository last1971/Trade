<template>
    <v-edit-dialog @open="open" @save="save">
        {{ value[attribute] }}
        <template v-if="!disabled" v-slot:input>
            <v-text-field
                :rules="rules"
                single-line
                v-model="editingValue"
            />
        </template>
    </v-edit-dialog>
</template>

<script>
    export default {
        name: "EditField",
        props: {
            value: {type: Object, required: true},
            model: {type: String, required: true},
            attribute: {type: String, required: true},
            rules: {type: Array, default: () => []},
            options: {type: Object, default: () => ({})},
            disabled: {type: Boolean, default: false}
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
                    this.$store.dispatch(this.model + '/UPDATE', {item, options: this.options});
                }
            }
        }
    }
</script>

<style scoped>

</style>
