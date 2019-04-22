ARG BASE_TAG=7.3-apache

FROM composer:1.8
ADD app /app
RUN cd /app && rm -rf vendor && composer install

FROM php:${BASE_TAG}

# Build-time metadata as defined at http://label-schema.org
ARG BUILD_DATE
ARG VCS_REF
ARG VERSION
LABEL org.label-schema.build-date=${BUILD_DATE} \
      org.label-schema.name="simple-tileserver" \
      org.label-schema.description="Simple PHP-Tileserver" \
      org.label-schema.url="https://hub.docker.com/r/stucky/simple-tileserver" \
      org.label-schema.vcs-ref=${VCS_REF} \
      org.label-schema.vcs-url="https://github.com/stuckyhm/simple-tileserver" \
      org.label-schema.version="${VERSION}" \
      org.label-schema.schema-version="1.0"

RUN a2enmod rewrite

RUN sed -i 's|DocumentRoot /var/www/html| DocumentRoot /var/www/html/web|g' /etc/apache2/sites-available/000-default.conf

COPY --from=0 /app /var/www/html

EXPOSE 80