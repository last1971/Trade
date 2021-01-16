import Vue from 'vue'
import Vuetify from 'vuetify'
import 'vuetify/dist/vuetify.min.css'
import ru from 'vuetify/es5/locale/ru'
import '@mdi/font/css/materialdesignicons.css'
import colors from 'vuetify/lib/util/colors'

Vue.use(Vuetify);

const primary = process.env.MIX_PRIMARY_COLOR || 'indigo';
const lighten = process.env.MIX_PRIMARY_LIGHTEN || 'lighten2';

const opts = {
    icons: {
        iconfont: 'mdi',
    },
    lang: {
        locales: {ru},
        current: 'ru',
    },
    theme: {
        themes: {
            light: {
                primary: colors[primary][lighten],
            },
        },
    },
};

export default new Vuetify(opts)
