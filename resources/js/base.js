module.exports = {
    methods: {
        __(key, replace = {}) {
            var translation = this.$page.props.language[key]
                ? this.$page.props.language[key]
                : key
            Object.keys(replace).forEach(function (key) {
                translation = translation.replace(':' + key, replace[key])
            });
            return translation
        },
    },
}