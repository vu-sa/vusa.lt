const path = require('path');

module.exports = {
  resolve: {
    alias: {
      '@': path.resolve('resources/js'),
      'ziggy': path.resolve("vendor/tightenco/ziggy/dist/vue")
    },
    extensions: ['.ts', '.js']
  },
  output: {
    chunkFilename: 'js/[name].js?id=[chunkhash]',
  },
  module: {
    rules: [
      // not working properly
      // {
      //   test: /\.scss$/,
      //   use: [
      //     'vue-style-loader',
      //     'css-loader',
      //     'sass-loader'
      //   ]
      // },
      // working
      {
        test: /\.ts$/,
        loader: 'ts-loader',
        options: { appendTsSuffixTo: [/\.vue$/] },
        exclude: /node_modules/,
      }
    ]
  },
};
