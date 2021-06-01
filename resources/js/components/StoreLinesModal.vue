<template>
    <v-dialog v-model="isActiveProxy">
        <template v-if="withActivator" v-slot:activator="{ on }">
            <v-btn v-on="on"
                   :icon="icon"
                   :x-small="xSmall"
                   :plain="plain"
                   :rounded="rounded"
                   :class="textClass"
            >
                <slot name="button">
                    {{ text }}
                </slot>
            </v-btn>
        </template>
        <v-card>
            <v-card-title class="headline">
                <v-btn @click="options.leftovers = !options.leftovers" icon left>
                    <v-icon v-if="options.leftovers">
                        mdi-chevron-right
                    </v-icon>
                    <v-icon v-else>
                        mdi-chevron-left
                    </v-icon>
                </v-btn>
                <v-spacer/>
                <span class="headline">{{ title }}</span>
                <v-spacer/>
                <v-btn @click="isActiveProxy = false" icon right>
                    <v-icon color="red">
                        mdi-close
                    </v-icon>
                </v-btn>
            </v-card-title>
            <v-divider></v-divider>
            <v-data-table
                :headers="headers"
                :items="items"
                :loading="loading"
                :options.sync="options"
                :server-items-length="total"
                item-key="SKLADINCODE"
                :loading-text="loadingText"
                class="mx-2"
            >
                <template v-slot:item.DATA="{ item }">
                    {{ item.DATA | formatDate }}
                </template>
                <template v-slot:item.DATA_DOC="{ item }">
                    {{ item.DATA_DOC | formatDate }}
                </template>
                <template v-slot:item.QUAN="{ item }">
                    <span v-if="options.leftovers">
                        {{ item.QUAN - item.fifos_sum_q_u_a_n }}
                    </span>
                    <span v-else>
                        {{ item.QUAN }}
                    </span>
                </template>
                <template v-slot:item.entry.PRICE="{ item }">
                    {{ item.entry.PRICE | formatRub }}
                </template>
                <template v-slot:item.entry.SUMMAP="{ item }">
                    {{ item.entry.PRICE * item.QUAN | formatRub }}
                </template>
            </v-data-table>
        </v-card>
    </v-dialog>
</template>

<script>
import tableMixin from "../mixins/tableMixin";

export default {
    name: "StoreLinesModal",
    mixins: [tableMixin],
    props: {
        good: {
            type: Object,
            required: true,
        },
        text: {
            type: [String, Number],
            default: 0,
        },
        textClass: {
            type: String,
            default: ''
        },
        icon: {
            type: Boolean,
            default: false,
        },
        xSmall: {
            type: Boolean,
            default: false,
        },
        plain: {
            type: Boolean,
            default: false,
        },
        rounded: {
            type: Boolean,
            default: false,
        },
        withActivator: {
            type: Boolean,
            default: true,
        },
        isActive: {
            type: Boolean,
            default: false,
        }
    },
    data() {
        return {
            mobileFiltersVisible: false,
            dependent: true,
            isActiveProxy: this.isActive,
            options: {
                with: ['entry.seller'],
                filterAttributes: [
                    'GOODSCODE',
                ],
                filterOperators: ['='],
                filterValues: [this.good.GOODSCODE],
                sortBy: ['DATA'],
                sortDesc: [true],
                itemsPerPage: 15,
                page: 1,
                leftovers: false,
            }
        }
    },
    computed: {
        headers() {
            return this.$store.getters['STORE-LINE/HEADERS'];
        },
        model() {
            return 'STORE-LINE';
        },
        name() {
            return this.good.name.NAME;
        },
        title() {
            return this.options.leftovers ? 'Останки для ' + this.name : 'Приходы для ' + this.name;
        },
    },
    watch: {
        isActive(v) {
            if (v) this.isActiveProxy = v;
        },
        isActiveProxy(v) {
            if (!v) this.$emit('update:isActive', v);
        },
    }
}
</script>

<style scoped>

</style>
