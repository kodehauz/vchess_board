const webpack = require('webpack');
const path = require('path');

module.exports = {
  entry: {
    'vchess-board': './src/main.js',
    'vchess-board.min': './src/main.js',
  },
  devtool: 'source-map',
  output: {
    path: path.resolve(__dirname, 'dist'),
    filename: '[name].js'
  },
  plugins: [
    new webpack.optimize.UglifyJsPlugin({
      include: /\.min\.js$/,
      minimize: true
    })
  ],
  module: {
    rules: [
      {
        test: /\.vue$/,
        loader: 'vue-loader',
      },
    ],
  },
  resolve: {
    alias: {
      'vue$': 'vue/dist/vue.esm.js',
    }
  }
};
