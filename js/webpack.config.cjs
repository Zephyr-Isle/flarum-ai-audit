const config = require('flarum-webpack-config');

module.exports = config({
  modules: {
    admin: './admin.js',
    forum: './src/forum/index.ts',
    common: './src/common/index.ts',
  },
});
