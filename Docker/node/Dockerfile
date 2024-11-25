FROM node:latest


RUN npm install -g npm@9.8.0

# Generate the build of the application
# RUN nset NODE_OPTIONS
# RUN export NODE_OPTIONS=--openssl-legacy-provider
# устанавливаем простой HTTP-сервер для статики
RUN npm install -g http-server

EXPOSE 8080
CMD [ "http-server", "dist" ]

USER node
