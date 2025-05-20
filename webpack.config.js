const defaultConfig = require("@wordpress/scripts/config/webpack.config.js");
const TerserPlugin = require("terser-webpack-plugin");
const path = require("path");

module.exports = {
  ...defaultConfig,
  ...{
    optimization: {
      minimize: true,
      minimizer: [new TerserPlugin()],
    },
    entry: {
      ...defaultConfig.entry(),
      elementorWidget: path.join(path.resolve(), "src/elementorWidget.js")
    }
  },
};
