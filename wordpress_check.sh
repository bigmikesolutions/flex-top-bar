#!/bin/bash

docker compose -f staging/docker-compose.yml run --rm wp-cli plugin check flex-top-bar --allow-root \
   --exclude-directories=freemius > wordpress_check.log
