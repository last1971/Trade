<template>
    <v-dialog v-model="adding" max-width="800">
        <template v-slot:activator="{ on }">
            <v-btn rounded color="success" v-on="on">
                <v-icon left>mdi-plus</v-icon>
                Добавить ед.изм.
            </v-btn>
        </template>
        <v-card>
            <v-card-title class="headline">
                <span class="headline">Создать новую единицу измерения</span>
                <v-spacer/>
                <v-btn @click="close" icon right>
                    <v-icon color="red">
                        mdi-close
                    </v-icon>
                </v-btn>
            </v-card-title>
            <v-divider/>
            <v-card-text>
                <v-text-field label="Код"
                             v-model="unitCode.code"
                             :rules="[rules.isNumber, rules.required]"
                />
                <v-text-field label="Наименование" v-model="unitCode.name" :rules="[rules.required]"
                />
            </v-card-text>
            <v-card-actions class="d-flex justify-end">
                <v-btn rounded color="success" :disabled="saveNotPossible" @click="save">
                    <v-icon left>
                        mdi-content-save
                    </v-icon>
                    Сохранить
                </v-btn>
                <v-btn rounded color="error" @click="close">
                    <v-icon left>
                        mdi-close
                    </v-icon>
                    Отменить
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
import utilsMixin from "../../mixins/utilsMixin";

export default {
    name: "UnitCodeAdd",
    mixins:[utilsMixin],
    data() {
        return {
            adding: false,
            unitCode: {
                code: null,
                name: null,
            },
        }
    },
    computed: {
        saveNotPossible() {
            return this.rules.required(this.unitCode.name) !== true
                || this.rules.required(this.unitCode.code) !== true
                || this.rules.isNumber(this.unitCode.code) !== true;
        }
    },
    methods: {
        close() {
            this.adding = false;
        },
        async save() {
            try {
                await this.$store.dispatch('UNIT-CODE/CREATE', {item: this.unitCode});
                this.unitCode = {
                    code: null,
                    name: null,
                };
                this.$emit('reload');
                this.close();
            } catch (e) {
                console.error(e);
            }
        }
    }
}
</script>

<style scoped>

</style>
