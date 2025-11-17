FROM php:7.4-fpm

# Install system dependencies, git, SSH, and PHP extensions
RUN apt-get update && apt-get install -y \
        git \
        openssh-client \
        iproute2 \
        unzip \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        default-mysql-client \
        iputils-ping \
        && docker-php-ext-install pdo pdo_mysql mysqli gd zip \
        && rm -rf /var/lib/apt/lists/*

# Configure SSH for GitHub (for private repo clone)
RUN mkdir -p /root/.ssh && ssh-keyscan github.com >> /root/.ssh/known_hosts

# Configure PHP-FPM to listen on TCP 9000
RUN sed -i 's|^listen = .*|listen = 0.0.0.0:9000|' /usr/local/etc/php-fpm.d/www.conf \
    && echo "listen.owner = www-data" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "listen.group = www-data" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm = dynamic" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_children = 10" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.start_servers = 2" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.min_spare_servers = 1" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_spare_servers = 3" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "catch_workers_output = yes" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "php_admin_value[error_log] = /var/log/php/php-fpm.log" >> /usr/local/etc/php-fpm.d/www.conf

# Prepare log directory
RUN mkdir -p /var/log/php && touch /var/log/php/php-fpm.log && chmod 777 /var/log/php/php-fpm.log

# Clone your repo (using BuildKit SSH mount)
RUN --mount=type=ssh git clone git@github.com:Anes-BHD/library.git /tmp/library

# Copy files to web root
RUN mkdir -p /var/www/html \
    && cp -a /tmp/library/. /var/www/html/ \
    && rm -rf /tmp/library

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 9000

# Start PHP-FPM in foreground
CMD ["php-fpm", "-F", "-R"]