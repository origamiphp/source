FROM nginx:1.15-alpine

# Install HTTPS requirements
RUN \
    apk add --no-cache openssl shadow && \
    mkdir -p /etc/nginx/ssl

# Assign a new UID to avoid using a generated value with docker-sync
RUN usermod -u 1000 nginx

# Install custom entrypoint
COPY entrypoint.sh /usr/local/bin/docker-custom-entrypoint
RUN chmod 777 /usr/local/bin/docker-custom-entrypoint
CMD ["nginx", "-g", "daemon off;"]
ENTRYPOINT ["docker-custom-entrypoint"]
