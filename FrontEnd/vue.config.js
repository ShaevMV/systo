const { defineConfig } = require('@vue/cli-service');

module.exports = defineConfig({
    transpileDependencies: true,
    devServer: {
        port: 8080,
        host: '127.0.0.1',
        allowedHosts: ['org.tickets.loc', 'localhost'],
        client: {
            webSocketURL: {
                hostname: 'org.tickets.loc',
                port: 8080,
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