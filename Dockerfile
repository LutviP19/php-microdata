# Menggunakan versi spesifik yang stabil (contoh: 1.4)

# Production
# FROM dunglas/frankenphp:1.12.4-php8.5

# Development
FROM dunglas/frankenphp:1.12-builder-php8.5

# 1. Install dependensi OS untuk FFI dan Testing
# (Non Alpine)
# 1. Install dependencies dasar, ApacheBench (ab), dan perlengkapan sertifikat/gnupg
RUN apt-get update && apt-get install -y \
    nano \
    libffi-dev \
    apache2-utils \
    ca-certificates \
    gnupg2 \
    curl \
    && rm -rf /var/lib/apt/lists/*

# 2. Tambahkan repository resmi k6 dan install k6
# K6: Download langsung binary Linux AMD64 matang dari rilis GitHub resmi
# (Metode ini dijamin 100% lolos dari error repositori/keyrings apt)
RUN curl -sSLo /tmp/k6.tar.gz https://github.com/grafana/k6/releases/download/v0.51.0/k6-v0.51.0-linux-amd64.tar.gz \
    && tar -xzf /tmp/k6.tar.gz -C /tmp \
    && mv /tmp/k6-v0.51.0-linux-amd64/k6 /usr/local/bin/k6 \
    && chmod +x /usr/local/bin/k6 \
    && rm -rf /tmp/k6.tar.gz /tmp/k6-v0.51.0-linux-amd64

# 3. Download dan install tool 'hey' (Mengambil binary Linux 64-bit versi stabil terbaru)
RUN curl -sSLo /usr/local/bin/hey https://hey-release.s3.us-east-2.amazonaws.com/hey_linux_amd64 \
    && chmod +x /usr/local/bin/hey

# 4. Install ekstensi PHP melalui helper resmi FrankenPHP
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

# 5. Salin file php.ini kustom Anda ke direktori konfigurasi PHP kontainer
COPY php.ini $PHP_INI_DIR/php.ini

# Salin source code aplikasi (Langkah ini akan mematuhi .dockerignore, 
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

# Development
COPY test /app/test

# 6. Salin Caddyfile kustom (Ditaruh di paling bawah agar aman dari .dockerignore 
# atau jika CaddyDocker berada di luar, pastikan jalurnya tepat)
# COPY CaddyDocker /etc/caddy/Caddyfile