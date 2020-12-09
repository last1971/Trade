<template>
    <v-dialog
        v-model="dialog"
        width="500"
    >
        <template v-slot:activator="{ on, attrs }">
            <v-btn
                icon
                v-bind="attrs"
                v-on="on"
            >
                <v-icon>mdi-table-headers-eye</v-icon>
            </v-btn>
        </template>
        <v-card>
            <v-card-title class="headline grey lighten-2">
                <span>Столбцы</span>
                <v-spacer/>
                <v-btn @click="dialog = false" icon right>
                    <v-icon color="red">
                        mdi-close
                    </v-icon>
                </v-btn>
            </v-card-title>
            <v-card-text>
                <v-container>
                    <v-row v-for="header in headers" :key="header.value">
                        <v-checkbox v-model="selected"
                                    @change="setHidden(header)"
                                    :label="header.text"
                                    :value="header.value"
                        />
                    </v-row>
                </v-container>
            </v-card-text>
        </v-card>
    </v-dialog>
</template>

<script>
export default {
    name: "SelectHeaders",
    props: {
        model: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            dialog: false,
        }
    },
    computed: {
        headers() {
            return this.$store.getters[this.model + '/HEADERS'].filter((header) => header.value !== 'actions');
        },
        selected: {
            get() {
                return this.headers.filter((header) => !header.hidden).map((header) => header.value);
            },
            set(v) {

            }
        }
    },
    methods: {
        setHidden(header) {
            this.$store.commit(this.model + '/TOGGLE-HEADER', header.value);
        }
    }
}
</script>

<style scoped>

</style>
