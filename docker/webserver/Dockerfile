FROM php:7-apache

RUN apt update && apt -y upgrade && docker-php-ext-install mysqli

RUN ln -s /etc/apache2/mods-available/ssl.* /etc/apache2/mods-enabled/ \
    && ln -s /etc/apache2/mods-available/socache_shmcb.* /etc/apache2/mods-enabled/ \
    && ln -s /etc/apache2/mods-available/rewrite.* /etc/apache2/mods-enabled/ \
    && ln -s /etc/apache2/mods-available/expires.* /etc/apache2/mods-enabled/ \
    && ln -s /etc/apache2/mods-available/xml2enc.* /etc/apache2/mods-enabled/ \
    && ln -s /etc/apache2/mods-available/slotmem_shm.* /etc/apache2/mods-enabled/ \
    && ln -s /etc/apache2/mods-available/proxy* /etc/apache2/mods-enabled/

