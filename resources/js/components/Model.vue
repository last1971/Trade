<template>
    <div v-if="model">
        <component :is="lines" :key="key" :value="model" @input="test"/>
        <v-bottom-navigation color="primary" horizontal>
            <v-btn :to="links.previous">
                <span>Предыдущая</span>
                <v-icon>mdi-step-backward</v-icon>
            </v-btn>
            <v-spacer></v-spacer>
            <v-btn :to="links.next">
                <v-icon>mdi-step-forward</v-icon>
                <span>Следующая</span>
            </v-btn>
        </v-bottom-navigation>
    </div>
</template>

<script>
    import moment from "moment";

    export default {
        name: "Model",
        props: {
            value: {
                type: String,
                required: true,
            },
            lines: {
                type: Object,
                required: true,
            },
            with: {
                type: Array,
                default: () => [],
            },
            aggregateAttributes: {
                type: Array,
                default: () => [],
            },
            number: {
                type: String,
                required: true,
            },
            date: {
                type: String,
                required: true,
            },
            name: {
                type: String,
                required: true,
            },
        },
        data() {
            return {
                previousValue: null,
                key: -1,
            }
        },
        computed: {
            MODEL() {
                return _.toUpper(this.value);
            },
            model() {
                //debugger;
                if (this.$route.name !== this.value || this.key === this.$route.params.id) return this.previousValue;
                const model =
                    this.$route.params.id !== undefined
                        ? this.$store.getters[this.MODEL + '/GET'](this.$route.params.id)
                        : null;
                // if (this.$route.params.id === '0' && this.MODEL === 'INVOICE' && model) {
                //    console.log('Was 0');
                //    model.data = moment().format('Y-MM-DD');
                // }
                if (!model || (!this.with.reduce((sum, v) => sum && model[v] !== undefined, true) && model[this.$store.getters[this.MODEL + '/KEY']] !== 0)) {
                    this.getModel();
                } else {
                    this.previousValue = model;
                    this.key = this.$route.params.id;
                    if (this.key) {
                        const text = this.date
                            ? `${this.name} № ${this.previousValue[this.number]} от
                           ${this.$options.filters.formatDate(this.previousValue[this.date])}`
                            : `${this.name} ${_.get(this.previousValue, this.number)}`
                        this.$store.commit('BREADCRUMBS/PUT', {
                            text,
                            to: {name: this.value, params: {id: this.key}},
                            disabled: true,
                        });
                    }
                }
                return this.previousValue;
            },
            links() {
                const func = this.$store.getters[this.MODEL + '/GET_LINK'];
                const key = this.model[this.$store.getters[this.MODEL + '/KEY']];
                return func(key);
            }
        },
        methods: {
            getModel() {
                if (this.$route.params.id)
                    this.$store.dispatch(
                        this.MODEL + '/GET',
                        {
                            id: this.$route.params.id,
                            query: {
                                with: this.with,
                                aggregateAttributes: this.aggregateAttributes,
                            }
                        }
                    );
            },
            test(val) {
                this.previousValue = val;
            }
        }
    }
</script>

<style scoped>

</style>
