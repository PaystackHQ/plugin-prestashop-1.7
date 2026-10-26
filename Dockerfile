FROM prestashop/prestashop:9.1.5-5.0-classic-8.3-apache

WORKDIR /var/www/html/

COPY ./paystack ./modules/paystack
