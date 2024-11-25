#!/usr/bin/env sh
set -eu

# shellcheck disable=SC2016
envsubst '
  ${ENV_ORG_BACKEND_HOST} ${ENV_VHOD_BACKEND_HOST} ${ENV_DRUG_BACKEND_HOST} ${ENV_SPISOK_BACKEND_HOST} ${ENV_ORG_FRONTEND_HOST}
  ${ENV_ORG_BACKEND_PATCH} ${ENV_ORG_FRONT_PATCH} ${ENV_VHOD_PATCH} ${ENV_DRUG_PATCH} ${ENV_SPISOK_PATCH}
' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

exec "$@"