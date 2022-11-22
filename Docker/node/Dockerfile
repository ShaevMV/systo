FROM node:latest

RUN npm install -g npm
RUN npm install --global @vue/cli

# устанавливаем простой HTTP-сервер для статики
RUN npm install -g http-server

EXPOSE 8080
CMD [ "http-server", "dist" ]

USER node
