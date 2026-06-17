# Menggunakan versi spesifik yang stabil (contoh: 1.4)
FROM dunglas/frankenphp:1.12.4-php8

# 1. Install dependensi OS untuk FFI (Debian-based agar kompatibilitas tinggi di lokal)
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

# 3. Salin file php.ini kustom Anda ke direktori konfigurasi PHP kontainer
COPY php.ini $PHP_INI_DIR/php.ini

# 4. Salin Caddyfile kustom
COPY Caddyfile-docker /etc/caddy/Caddyfile

# 5. Salin source code aplikasi
COPY . /app
