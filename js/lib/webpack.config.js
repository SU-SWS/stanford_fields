const path = require('path')
const HtmlWebpackPlugin = require('html-webpack-plugin')
const TerserPlugin = require('terser-webpack-plugin')
const Dotenv = require('dotenv-webpack');

module.exports = ({ dev, prod }) => {
  const isDev = dev === true
  const isProd = prod === true

  /** @type { import('webpack').Configuration } */
  const config = {
    mode: isProd ? 'production' : 'development',
    target: 'web',
    resolve: {
      extensions: ['.js', '.json', '.ts', '.tsx'],
      /**
       * From the docs to make Webpack compile Preact:
       * https://preactjs.com/guide/v10/getting-started#aliasing-in-webpack
       */
      alias: {
        react: 'preact/compat',
        'react-dom/test-utils': 'preact/test-utils',
        'react-dom': 'preact/compat', // Must be below test-utils
        'react/jsx-runtime': 'preact/jsx-runtime',
      },
    },
    devServer: {
      port: 6464,
      hot: false,
      // Serve the module css so the dev template matches Drupal.
      static: { directory: path.join(__dirname, '../../css') },
    },
    devtool: false,
    entry: {
      'select-list-filters': './select-lists/select-list-filters.island.tsx',
      'hierarchy-label-select-lists': './hierarchy-label-select-lists/hierarchy-label-select-list-filters.island.tsx',
      'hierarchy-label-checkboxes': './hierarchy-label-checkboxes/hierarchy-label-checkbox-filters.island.tsx',
    },
    output: {
      path: path.join(__dirname, '/../dist'),
      filename: '[name].island.js',
      // Avoid clashing with other webpack bundles on the page.
      uniqueName: 'stanfordFieldsPreact',
    },
    module: {
      rules: [
        {
          test: /\.(js|ts|tsx)$/,
          exclude: [/node_modules/],
          use: [
            {
              loader: 'babel-loader',
              options: {
                babelrc: false,
                presets: [
                  '@babel/preset-typescript',
                  ['@babel/preset-react', { runtime: 'automatic' }],
                  // Targets come from the browserslist in package.json.
                  ['@babel/preset-env', { modules: false }],
                ],
              },
            },
          ],
        },
        {
          test: /\.css$/i,
          use: ['css-loader'],
        },
        {
          test: /\.(png|jpe?g|gif)$/i,
          use: [
            {
              loader: 'file-loader',
            },
          ],
        },
      ],
    },
    plugins: [
      new HtmlWebpackPlugin({
        template: 'template.html',
        /**
         * Islands are served from /islands in dist so we don't pollute the root domain since these islands are
         * embedded into websites we do not control.
         *
         * In dev mode, we serve islands and the index.html from the root since it's dev mode. For production,
         * the index.html file is served from the root.
         */
        publicPath: isDev ? '/' : '/islands',
        filename: isDev ? 'index.html' : '../index.html',
      }),
      new Dotenv({path: isDev ? './.env.local': '', silent: true})
    ],
    stats: 'errors-warnings',
    optimization: {
      minimize: true,
      minimizer: [new TerserPlugin()],
      // Share preact and the select list between the islands so pages with
      // multiple filter types only download them once. The chunk names must
      // match the libraries in stanford_fields.libraries.yml.
      runtimeChunk: { name: 'shared-runtime' },
      splitChunks: {
        cacheGroups: {
          default: false,
          defaultVendors: false,
          preact: {
            test: /[\\/]node_modules[\\/]preact[\\/]/,
            name: 'shared-preact',
            chunks: 'all',
            enforce: true,
            priority: 20,
          },
          selectList: {
            test: /[\\/](node_modules|components)[\\/]/,
            name: 'shared-select-list',
            chunks: (chunk) => ['select-list-filters', 'hierarchy-label-select-lists'].includes(chunk.name),
            enforce: true,
            priority: 10,
          },
        },
      },
    },
  }

  return config
}
