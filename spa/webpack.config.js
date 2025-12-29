// webpack.config.js
const webpack = require('webpack');
const Encore = require('@symfony/webpack-encore');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const path = require('path'); // Добавьте эту строку

Encore
    .setOutputPath('public/')
    .setPublicPath('/')
    .cleanupOutputBeforeBuild()
    .addEntry('app', './src/app.js')
    .enablePreactPreset()
    .enableSassLoader()
    .enableSingleRuntimeChunk()
    .addPlugin(new HtmlWebpackPlugin({
        template: 'src/index.ejs',
        filename: 'index.html',
        alwaysWriteToDisk: true,
        templateParameters: {
            basePath: '/' // или ваш путь, если приложение в поддиректории
        }
    }))
    .addPlugin(new webpack.DefinePlugin({
        'ENV_API_ENDPOINT': JSON.stringify('https://127.0.0.1:8000/'),
    }))
;

module.exports = Encore.getWebpackConfig();