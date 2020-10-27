FROM ubuntu:bionic

RUN set -xe \
    && echo "Europe/Moscow" > /etc/timezone \
    && apt-get -y update \
    && export DEBIAN_FRONTEND=noninteractive \
    && apt-get -y install --no-install-recommends \
       gnupg wget curl software-properties-common \
    && curl http://nginx.org/keys/nginx_signing.key | apt-key add - \
    && echo "deb https://packages.nginx.org/unit/ubuntu/ bionic unit"  | tee -a /etc/apt/sources.list \
    && add-apt-repository ppa:ondrej/php \
    && apt-get -y update \
    && apt-get -y install git runit unit unit-php libphp7.3-embed \
    && unitd --version

ARG build_env=devel
ENV BUILD_ENV=$build_env
ENV XDEBUG_PORT=9000
ENV XDEBUG_HOST=127.0.0.1
ENV XDEBUG_IDKEY=DEFKEY

RUN DEBIAN_FRONTEND=noninteractive apt-get -y install --no-install-recommends \
    php7.3-cli php7.3-curl php7.3-fpm php7.3-gd \
    php7.3-intl php7.3-json php7.3-mbstring \
    php7.3-pgsql php7.3-xml php7.3-zip php7.3-redis  php7.3-soap \

    && bash -c "if [ "$BUILD_ENV" == "devel" ]; then apt-get -y install --no-install-recommends php7.3-xdebug; fi" \
    && rm -rf /var/lib/apt/lists/*

ADD ./ /var/www/
ADD ./docker/conf/unit/config.json /tmp/config.json
ADD ./docker/service /etc/service
ADD ./docker/runit_entry /runit_entry
ADD ./docker/conf/php /tmp/php

RUN chmod a+x /runit_entry && \
    chmod -R a+x /etc/service/ && \
    chown -R www-data /var/www/

RUN bash -c "if [ "$BUILD_ENV" == "devel" ]; \
    then { \
        echo 'zend_extension=xdebug'; \
        echo 'xdebug.default_enable = 1'; \
        echo 'xdebug.remote_enable = 1'; \
        echo 'xdebug.remote_autostart=1'; \
        echo 'xdebug.remote_connect_back=0'; \
        echo 'xdebug.remote_host = $XDEBUG_HOST'; \
        echo 'xdebug.remote_port = $XDEBUG_PORT'; \
        echo 'xdebug.remote_handler = dbgp'; \
        echo 'xdebug.idekey = $XDEBUG_IDKEY'; \
        } > /etc/php/7.3/mods-available/xdebug.ini; \
    fi"

RUN bash -c "if [ "$BUILD_ENV" == "devel" ]; \
   then \
       cp /tmp/php/params-dev.ini /etc/php/7.3/mods-available/params.ini; \
       ln -s /etc/php/7.3/mods-available/params.ini /etc/php/7.3/embed/conf.d/90-params.ini; \
       ln -s /etc/php/7.3/mods-available/params.ini /etc/php/7.3/cli/conf.d/90-params.ini; \
   else \
       cp /tmp/php/params-prod.ini /etc/php/7.3/mods-available/params.ini; \
       ln -s /etc/php/7.3/mods-available/params.ini /etc/php/7.3/embed/conf.d/90-params.ini; \
       ln -s /etc/php/7.3/mods-available/params.ini /etc/php/7.3/cli/conf.d/90-params.ini; \
   fi"

ADD https://getcomposer.org/installer /tmp/composer
RUN php /tmp/composer --version=1.10.16 --install-dir=/usr/bin --filename=composer \
    && su -s /bin/bash www-data -c "if [ "$BUILD_ENV" == "devel" ]; then echo 'Skipping deps installing...'; else cd /var/www && composer install; fi"

RUN unitd --control unix:/var/run/unit.sock \
    && curl -X PUT --data-binary @/tmp/config.json --unix-socket \
    /var/run/unit.sock http://localhost/config/ \
    && kill `pidof unitd` && cat /var/log/unit.log

CMD ["unitd", "--no-daemon"]
