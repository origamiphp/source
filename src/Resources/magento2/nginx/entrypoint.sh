#!/usr/bin/env sh
set -euo pipefail

if [[ ! -f /etc/nginx/ssl/custom.pem || ! -f /etc/nginx/ssl/custom.key ]]; then
    openssl req -subj '/CN=localhost' -days 365 -x509 -newkey rsa:4096 -nodes \
        -out /etc/nginx/ssl/custom.pem \
        -keyout /etc/nginx/ssl/custom.key
fi

exec "$@"
