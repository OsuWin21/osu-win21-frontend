FROM debian:12

WORKDIR /app

COPY . .

EXPOSE 8000

ENTRYPOINT ["/bin/bash", "-c", "$(curl -fsSL https://php.new/install/linux/8.2) && composer install && php artisan serve"]