export default {
    beforeRouteEnter(to, from, next) {
        next(vm => {
            if (vm.$store.getters['AUTH/IS_LOGGEDIN']) vm.$router.push({name: 'home'});
        })
    }
}
