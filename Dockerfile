# Menggunakan versi spesifik yang stabil (contoh: 1.4)
FROM dunglas/frankenphp:1.12.4-php8

# 1. Install dependensi OS untuk FFI
RUN apt-get update && apt-get install -y \
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
COPY . /app

# 4. Salin Caddyfile kustom (Ditaruh di paling bawah agar aman dari .dockerignore 
# atau jika CaddyDocker berada di luar, pastikan jalurnya tepat)
COPY CaddyDocker /etc/caddy/Caddyfile