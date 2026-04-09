const { defineConfig } = require('@vue/cli-service');

module.exports = defineConfig({
    transpileDependencies: true,
    devServer: {
        port: 8080,
        host: '0.0.0.0',
        allowedHosts: ['org.tickets.loc', 'localhost', 'api.tickets.loc'],
        client: {
            webSocketURL: {
                hostname: 'org.tickets.loc',
                port: process.env.VUE_APP_WS_PORT || 50080,
            },
        },
    },
    chainWebpack: config => {
        config.plugin('define').tap(definitions => {
            definitions[0]['process.env'].VUE_APP_API_URL = JSON.stringify(process.env.VUE_APP_API_URL || 'https://api.spaceofjoy.ru/');
            return definitions;
        });
    },
});