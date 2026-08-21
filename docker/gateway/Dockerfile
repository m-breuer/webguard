FROM nginx:1.28-alpine

COPY docker/gateway/nginx.conf /etc/nginx/nginx.conf
COPY docker/gateway/proxy-headers.conf /etc/nginx/proxy-headers.conf

HEALTHCHECK --interval=10s --timeout=3s --start-period=10s --retries=6 CMD wget --quiet --output-document=/dev/null http://127.0.0.1:8080/_health/gateway || exit 1
