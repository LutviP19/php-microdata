# Menggunakan versi spesifik yang stabil (contoh: 1.4)

# Production
# FROM dunglas/frankenphp:1.12.4-php8.5

# Development
FROM dunglas/frankenphp:1.12-builder-php8.5

# 1. Install dependensi OS untuk FFI 
# (Non Alpine)
RUN apt-get update && apt-get install -y \
    nano \
    libffi-dev \
    && rm -rf /var/lib/apt/lists/*

# 2. Install ekstensi PHP melalui helper resmi FrankenPHP
RUN install-php-extensions \
    ffi \
    redis \
    pdo_sqlite \
    pdo_mysql \
    opcache \
    zip \
    gd \
    intl

# Set working directory terlebih dahulu
WORKDIR /app

# 3. Salin file php.ini kustom Anda ke direktori konfigurasi PHP kontainer
COPY php.ini $PHP_INI_DIR/php.ini

# 5. Salin source code aplikasi (Langkah ini akan mematuhi .dockerignore, 
# sehingga file Caddy* otomatis bersih dan tidak masuk ke folder /app)

# dari root project ke dalam folder /app secara REKURSIF
# COPY . /app

# Jika Anda hanya ingin menyalin folder tertentu saja secara rekursif:
COPY app /app/app
COPY config /app/config
COPY cron /app/cron
COPY ffi /app/ffi
COPY public /app/public
COPY static /app/static
COPY storage /app/storage
COPY vendor /app/vendor
COPY views /app/views
COPY .env /app/.env
COPY CaddyDocker /app/Caddyfile
COPY worker.php /app/worker.php
COPY worker-event.php /app/worker-event.php

# 4. Salin Caddyfile kustom (Ditaruh di paling bawah agar aman dari .dockerignore 
# atau jika CaddyDocker berada di luar, pastikan jalurnya tepat)
# COPY CaddyDocker /etc/caddy/Caddyfile