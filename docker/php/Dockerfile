FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev default-mysql-client \
    && docker-php-ext-install pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# uploads do cofre de documentos (até 25 MB por arquivo)
RUN printf "upload_max_filesize=25M\npost_max_size=30M\nmemory_limit=512M\nmax_execution_time=600\n" > /usr/local/etc/php/conf.d/plano-teste.ini

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
# garante LF caso o arquivo tenha sido salvo com CRLF no Windows
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html
EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
