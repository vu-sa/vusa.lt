const mix = require('laravel-mix');
const path = require('path');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.copyDirectory('node_modules/tinymce/icons', 'public/js/icons')
    .copyDirectory('node_modules/tinymce/skins', 'public/js/skins')
    .copyDirectory('node_modules/tinymce/plugins', 'public/js/plugins')
    .copyDirectory('resources/fonts/', 'public/fonts')
    .js('resources/js/admin.js', 'public/js/admin.js')
    .js('resources/js/app.js', 'public/js/app.js')
    .js('resources/js/app-new.js', 'public/js/app-new.js').vue()
    .postCss('resources/css/app-new.css', 'public/css/app-new.css', [
        require('postcss-import'),
        require('tailwindcss'),
    ])
    .sass('resources/sass/admin.scss', 'public/css/admin.css')
    .sass('resources/sass/app.scss', 'public/css/app.css')
    .webpackConfig(webpack => {
        return {
            module: {
                rules: [
                    // any other rules
                    {
                        // Exposes jQuery for use outside Webpack build
                        test: require.resolve('jquery'),
                        use: [{
                            loader: 'expose-loader',
                            options: 'jQuery'
                        }, {
                            loader: 'expose-loader',
                            options: '$'
                        }]
                    }
                ]
            },

            resolve: {
                alias: {
                    jquery: "jquery/src/jquery",
                    '@': path.resolve('resources/js/new')
                }
            },

            plugins: [
                new webpack.ProvidePlugin({
                    $: 'jquery',
                    jQuery: 'jquery'
                })
            ]
        };
    });
    
if (mix.inProduction()) {
    mix.version();
}
