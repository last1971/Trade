<template>
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
                :value="proxy | formatDate"
                :label="'Позже' + (isMobile ?  ' указанной Даты' : '')"
                prepend-icon="mdi-calendar-edit"
                v-on="on"
                readonly
            />
        </template>
        <v-date-picker @input="datePicker = false"
                       first-day-of-week="1"
                       v-model="proxy"
        />
    </v-menu>
</template>

<script>
import moment from 'moment';

export default {
    name: "DatePicker",
    props: {
        value: {
            default: moment().format('Y-MM-DD'),
            validator: (v) => moment(v).isValid(),
        },
        isMobile: {
            type: Boolean,
            default: false,
        }
    },
    data() {
        return {
            datePicker: false,
        }
    },
    computed: {
        proxy: {
            get() {
                return this.value;
            },
            set(v) {
                this.$emit('input', v);
            }
        }
    }
}
</script>

<style scoped>

</style>
