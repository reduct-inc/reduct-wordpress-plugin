const defaultConfig = require("@wordpress/scripts/config/webpack.config.js");
const TerserPlugin = require("terser-webpack-plugin");

module.exports = {
  ...defaultConfig,
  ...{
    optimization: {
      minimize: true,
      minimizer: [new TerserPlugin()],
    },
  },
};
