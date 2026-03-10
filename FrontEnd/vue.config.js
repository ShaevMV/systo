module.exports = {
  devServer: {
    port: 8080,
  },
  chainWebpack: config => {
    config.plugin('define').tap(definitions => {
      definitions[0]['__APP_ENV__'] = JSON.stringify(process.env.VUE_APP_API_URL || 'http://api.tickets.loc/');
      return definitions;
    });
  },
};
