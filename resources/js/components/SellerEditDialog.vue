<template>
    <v-dialog v-model="edit" modal>
        <template v-slot:activator="{ on }">
            <v-btn icon v-on="on">
                <v-icon color="green">mdi-content-save-edit</v-icon>
            </v-btn>
        </template>
        <v-card>
            <v-card-title class="headline">
                <span class="headline">Поставщик</span>
                <v-spacer/>
                <v-btn @click="edit = false" icon right>
                    <v-icon color="red">
                        mdi-close
                    </v-icon>
                </v-btn>
            </v-card-title>
            <v-divider/>
            <v-card-text>
                <v-text-field label="Название" v-model="instance.NAMEPOST"/>
                <v-text-field label="ИНН" v-model="instance.INN"/>
                <v-text-field label="E-mail" v-model="instance.EMAIL"/>
            </v-card-text>
            <v-card-actions class="d-flex flex-row-reverse">
                <v-btn rounded @click="save" :disabled="disabled" right class="mr-2 mb-2">
                    <v-icon left color="success">mdi-content-save</v-icon>
                    <span>Сохранить</span>
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
export default {
    name: "SellerEditDialog",
    props: {
        value: { type: [ String, Number ]}
    },
    data() {
        return {
            instance: {
                NAMEPOST: null,
                EMAIL: null,
                INN: null,
            },
            edit: false,
        }
    },
    computed: {
        proxy() {
            return this.$store.getters['SELLER/GET'](this.value) || {
                NAMEPOST: null,
                EMAIL: null,
                INN: null,
            };
        },
        disabled() {
            return _.isEqual(this.instance, this.proxy);
        }
    },
    watch: {
        proxy() {
            this.reload();
        }
    },
    methods: {
        reload() {
            this.instance = _.cloneDeep(this.proxy);
        },
        async save() {
            if (this.instance.WHEREISPOSTCODE) {
                await this.$store.dispatch('SELLER/UPDATE', { item: this.instance });
            } else {
                const newSeller = await this.$store.dispatch('SELLER/UPDATE', { item: this.instance });
                this.$emit('input', newSeller.WHEREISPOSTCODE);
            }
        }
    }
}
</script>

<style scoped>

</style>
