# Set base image.
FROM ubuntu:18.04

# Set maintainer to Ayup Digital.
LABEL maintainer="Ayup Digital"

# Set needed environment variables before installing PHP.
ENV DEBIAN_FRONTEND noninteractive
ENV LANG en_GB.UTF-8
ENV LANGUAGE en_GB:en
ENV LC_ALL en_GB.UTF-8

# Install generic software.
RUN apt-get update \
    && apt-get install -y locales nginx curl wget zip unzip git software-properties-common supervisor \
    && locale-gen en_GB.UTF-8

# Install PHP.
RUN apt-add-repository -y ppa:ondrej/php \
    && apt-get update \
    && apt-get install -y php7.2-fpm php7.2-cli php7.2-gd php7.2-mysql php7.2-redis \
       php7.2-imap php7.2-mbstring php7.2-xml php7.2-curl php7.2-zip \
    && php -r "readfile('http://getcomposer.org/installer');" | php -- --1 --install-dir=/usr/bin/ --filename=composer \
    && composer global require hirak/prestissimo \
    && mkdir /run/php

# Cleanup.
RUN apt-get remove -y --purge software-properties-common \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Redirect NGINX output to stdout and stderr.
RUN ln -sf /dev/stdout /var/log/nginx/access.log \
    && ln -sf /dev/stderr /var/log/nginx/error.log

# Copy configuration files over for installed software.
COPY etc/nginx/nginx.conf /etc/nginx/nginx.conf
COPY etc/nginx/sites-available/default /etc/nginx/sites-available/default
COPY etc/php/7.2/fpm/php-fpm.conf /etc/php/7.2/fpm/php-fpm.conf
COPY etc/php/7.2/fpm/pool.d/www.conf /etc/php/7.2/fpm/pool.d/www.conf
COPY etc/supervisor/conf.d/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy the bootstrap script over and make executable.
COPY usr/local/bin/start-container.sh /usr/local/bin/start-container.sh
RUN chmod +x /usr/local/bin/start-container.sh

# Copy the packaged app (only used when deploying).
COPY packaged /var/www/html
RUN chown -R www-data: /var/www/html

# Expose port 80 for HTTP access.
EXPOSE 80

# Set the working directory to the project root.
WORKDIR /var/www/html

# Set default command to the bootstrap script.
CMD ["start-container.sh"]
