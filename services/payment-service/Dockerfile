FROM php:8.1-cli
WORKDIR /var/www
COPY composer.json .
RUN composer install --no-dev --no-interaction
COPY . .
EXPOSE 8003
CMD ["php", "-S", "0.0.0.0:8003", "-t", "public"]