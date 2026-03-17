FROM php:8.2-fpm-bookworm

# Install system dependencies
RUN apt-get update -qq && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libjpeg62-turbo-dev \
    libgmp-dev \
    libicu-dev \
    netcat-openbsd \
    zip \
    unzip \
    libzip-dev \
    git \
    curl \
    iputils-ping \
    redis \
    default-mysql-client \
    nano \
    procps \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql zip gd bcmath pcntl exif intl \
    && rm -rf /var/lib/apt/lists/*

# Install wkhtmltopdf with patched Qt (for PDF generation with JS support)
RUN curl -sL https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-3/wkhtmltox_0.12.6.1-3.bookworm_amd64.deb -o /tmp/wkhtmltox.deb \
    && apt-get install -y /tmp/wkhtmltox.deb \
    && rm /tmp/wkhtmltox.deb \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# Copy the entrypoint script
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
