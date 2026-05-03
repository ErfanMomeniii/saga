FROM php:8.1-cli
WORKDIR /var/www
COPY composer.json .
RUN composer install --no-dev --no-interaction
COPY . .
EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]