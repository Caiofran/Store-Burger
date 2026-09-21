#!/bin/sh
set -eu
trap 'exit 0' TERM INT
while :; do
    php /var/www/brasa/bin/worker.php || true
    sleep 30 &
    wait $!
done
