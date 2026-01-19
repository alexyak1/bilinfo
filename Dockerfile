FROM php:8.3-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy project files
COPY . .

# Install dependencies if composer.lock exists
RUN if [ -f composer.lock ]; then composer install --no-interaction --optimize-autoloader; fi

# Create data directory for SQLite
RUN mkdir -p data && chmod 777 data

EXPOSE 80

# Run PHP built-in server
CMD ["php", "-S", "0.0.0.0:80", "-t", "public"]
