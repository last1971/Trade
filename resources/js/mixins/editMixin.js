export default {
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
            return true;
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
            this.$store.dispatch(
                this.MODEL + '/UPDATE',
                {item: this.model, options: this.options}
            )
                .then(() => {
                })
                .catch(() => {
                })
                .then(() => {
                    this.loading = false;
                    this.initialModel();
                });
        }
    }
}
