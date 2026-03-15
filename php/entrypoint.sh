#!/bin/sh
set -e

echo "Starting entrypoint script..."

# Ensure Laravel storage and cache dirs are writable regardless of host user
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

exec "$@"
