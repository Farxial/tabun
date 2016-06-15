path = require 'path'
webpack = require 'webpack'
{keys} = require 'lodash'
fs = require 'fs'
ExtractTextPlugin = require 'extract-text-webpack-plugin'
isProduction = process.env.NODE_ENV == 'production'

vendors = [
  "lodash"
  "jquery"
  "classnames"
  "jquery.scrollto"
  "jed"
  "immutable"
  "react"
  "react-bootstrap"
  "react-dom"
  "bazooka"
]

aliases =
  "jquery.jqmodal": path.join __dirname, 'frontend', 'vendor', 'jquery.jqmodal.js'
  "jquery.ui": path.join __dirname, 'frontend', 'vendor', 'jquery.ui.js'
  "jquery.form": path.join __dirname, 'frontend', 'vendor', 'jquery.form.js'
  "jquery.markitup": path.join __dirname, 'frontend', 'vendor', 'jquery.markitup.js'
  "jquery.jcrop": path.join __dirname, 'frontend', 'vendor', 'jquery.jcrop.js'
  "jquery.file": path.join __dirname, 'frontend', 'vendor', 'jquery.file.js'

module.exports =
  context: path.join __dirname, 'frontend'
  cache: true
  devtool: "#source-map"

  entry:
    main: "./main"
    comments: "./comments"
    topics: "./topics"
    vendor: Array::concat keys(aliases), vendors

  output:
    path: path.join __dirname, 'static', '[hash]'
    publicPath: "./"
    filename: '[name].bundle.js'

  module:
    loaders: [
      {test: /\.jsx?$/, loader: 'babel-loader', exclude: /node_modules/, query: {presets: ['es2015', 'react']}}
      {test: /\.coffee$/, loader: 'coffee-loader'}
      {test: /\.styl$/, loader: ExtractTextPlugin.extract("style-loader", "css-loader!stylus-loader")}
      {test: /\.css$/, loader: ExtractTextPlugin.extract("style-loader", "css-loader")}
      {test: /\.po$/, loader: 'json!po?format=jed1.x'}
      {
        test: /.*\.(gif|png|jpg|jpeg|svg)$/,
        loaders: Array::concat(
          if isProduction then ['file?name=img/[hash:4].[ext]'] else ['file?name=img/[name].[ext]']
          if isProduction then ['image-webpack?optimizationLevel=7&interlaced=false'] else []
        )
      }
    ]

  resolve:
    alias: aliases
    extensions: ['', '.coffee', '.js', '.styl', '.css']
    modulesDirectories: ['node_modules', 'scripts', 'locale']

  plugins: Array::concat(
    if isProduction then [
      new webpack.optimize.UglifyJsPlugin()
    ] else []
    [
      new ExtractTextPlugin "[name].css"
      new webpack.optimize.DedupePlugin()
      new webpack.optimize.CommonsChunkPlugin name: 'vendor', minChunks: Infinity
      () ->
        @plugin("done", (stats) ->
          fs.writeFileSync(
            path.join(__dirname, "config", "frontend.version"),
            stats.hash
          )
        )
    ]
  )