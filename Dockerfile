FROM php:8.2-cli

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    libpq-dev \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Установка расширений PHP
RUN docker-php-ext-install zip pdo pdo_pgsql

# Установка Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# Рабочая директория
WORKDIR /app

# Команда по умолчанию
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]