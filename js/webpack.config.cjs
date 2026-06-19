const config = require('flarum-webpack-config');

module.exports = config({
  modules: {
    admin: './src/admin/index.ts',
    forum: './src/forum/index.ts',
    common: './src/common/index.ts',
  },
});
