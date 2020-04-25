<template>
    <div v-if="model">
        <component :is="lines" :key="key" :value="model"/>
    </div>
</template>

<script>
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
                key: 0,
            }
        },
        computed: {
            MODEL() {
                return _.toUpper(this.value);
            },
            model() {
                if (this.$route.name !== this.value) return this.previousValue;
                const model =
                    this.$route.params.id ? this.$store.getters[this.MODEL + '/GET'](this.$route.params.id) : null;
                if (!model || !this.with.reduce((sum, v) => sum && model[v], true)) {
                    this.getModel();
                } else {
                    this.previousValue = model;
                    this.key = this.$route.params.id;
                    this.$store.commit('BREADCRUMBS/PUT', {
                        text: `${this.name} № ${this.previousValue[this.number]} от
                            ${this.$options.filters.formatDate(this.previousValue[this.date])}`,
                        to: {name: this.value, params: {id: this.key}},
                        disabled: true,
                    });
                }
                return this.previousValue;
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
            }
        }
    }
</script>

<style scoped>

</style>
