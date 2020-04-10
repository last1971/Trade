<template>
    <v-container
        class="fill-height"
        fluid
    >
        <v-row
            align="center"
            justify="center"
        >
            <v-alert>Тут будет Прайсинг</v-alert>
        </v-row>
    </v-container>
</template>

<script>
    export default {
        beforeRouteEnter(to, from, next) {
            next(vm => {
                console.log('enter');
                vm.$store.commit('BREADCRUMBS/SET', [
                    {
                        text: 'Торговля',
                        to: {name: 'home'},
                        link: true,
                        disabled: true,
                    }
                ]);
            });
        }
    }
</script>
