export default {
    props: {
        value: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {
            model: {},
            datePicker: false,
            loading: false,
            errors: {},
            dependent: false,
        }
    },
    computed: {
        notEditable() {
            return true;
        },
        savePossible() {
            return _.isEqual(_.omit(this.model, ['DATA']), _.omit(this.value, ['DATA']));
        },
        options() {
            return this.$store.getters['AUTH/LOCAL_OPTION'](this.MODEL);
        },
        fillable() {
            return this.$store.getters[this.MODEL + '/FILLABLE'];
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
            this.errors = {};
            const oper = this.model[this.$store.getters[this.MODEL + '/KEY']] === 0
                ? '/CREATE' : '/UPDATE';
            this.$store.dispatch(
                this.MODEL + oper,
                {item: this.model, options: this.options}
            )
                .then((response) => {
                    if (oper === '/CREATE' && !this.dependent)
                        this.$router.replace({params: {id: response.data[this.$store.getters[this.MODEL + '/KEY']]}});
                    this.$emit('input', response.data)
                })
                .catch((error) => {
                    if (error.response && error.response.data.errors) {
                        this.errors = _.mapValues(error.response.data.errors, (v) => {
                            return _.isArray(v)
                                ? v.map((e) => this.$store.getters['ERROR-MESSAGE/GET'](e))
                                : this.$store.getters['ERROR-MESSAGE/GET'](v);
                        });
                    }
                })
                .then(() => {
                    this.loading = false;
                    this.initialModel();
                });
        }
    }
}
