const path = require('path');

module.exports = {
  entry: './src/index.jsx',
  output: {
    path: path.join(__dirname, '/dist'),
    filename: 'tipser.bundle.js',
  },
  module: {
    rules: [
      {
        test: /\.jsx?$/,
        resolve: {
          extensions: ['.jsx', '.js'],
        },
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
        },
      },
      {
        test: /\.css$/,
        use: ['style-loader', 'css-loader'],
      },
    ],
  },
};
