FROM php:8.4-cli-alpine

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy source and configuration files
COPY TimeString /app/TimeString
COPY test /app/test
COPY composer.json /app/composer.json

# Copy entrypoint script
COPY docker/php83/run-tests.sh /usr/local/bin/run-tests.sh
RUN chmod +x /usr/local/bin/run-tests.sh

ENTRYPOINT ["/usr/local/bin/run-tests.sh"]

