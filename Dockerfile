FROM prestashop/prestashop:1.7-7.2-apache

WORKDIR /var/www/html/

COPY ./paystack ./modules/paystack
