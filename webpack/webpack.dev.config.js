var webpack = require('webpack');
var path = require('path');

var parentDir = path.join(__dirname, '../');
console.log(parentDir);
module.exports = {
    entry: [
        path.join(parentDir, 'app/index.js')
    ],
    module: {
        rules: [{
            test: /\.(js|jsx)$/,
            exclude: /node_modules/,
            loader: 'babel-loader'
        },{
            test: /\.less$/,
            loaders: ["style-loader", "css-loder", "less-loader"]
        }]
    },
    output: {
        path: parentDir + '/dist',
        filename: 'bundle.js'
    },
    devServer: {
        contentBase: parentDir + '/public',
        historyApiFallback: true
    }
}
