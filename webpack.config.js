const path = require('path');

module.exports = {
    resolve: {
        alias: {
            '@': path.resolve('resources/js'),
        },
        extensions: ['.ts', '.js']
    },
    output: {
        chunkFilename: 'js/[name].js?id=[chunkhash]',
    },
    module: {
        rules: [
            // ... other rules omitted
      
            // this will apply to both plain `.scss` files
            // AND `<style lang="scss">` blocks in `.vue` files
            {
              test: /\.scss$/,
              use: [
                'vue-style-loader',
                'css-loader',
                'sass-loader'
              ]
            },
            {
                test: /\.ts$/,
                loader: 'ts-loader',
                options: { appendTsSuffixTo: [/\.vue$/] }
              }
          ]
        },
};
