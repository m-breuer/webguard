FROM oven/bun:latest

WORKDIR /var/www/html

COPY docker/node/entrypoint.sh /usr/local/bin/webguard-node-entrypoint
RUN chmod +x /usr/local/bin/webguard-node-entrypoint

ENTRYPOINT ["webguard-node-entrypoint"]
CMD ["bun", "run", "dev", "--host", "0.0.0.0", "--port", "5173"]
