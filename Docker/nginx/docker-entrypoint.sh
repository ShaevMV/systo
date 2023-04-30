#!/usr/bin/env sh
set -eu

# shellcheck disable=SC2016
envsubst '${ENV_ORG_BACKEND_HOST} ${ENV_VHOD_BACKEND_HOST} ${ENV_DRUG_BACKEND_HOST} ${ENV_SPISOK_BACKEND_HOST}' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

exec "$@"