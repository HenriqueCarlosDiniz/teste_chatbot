# Dockerfile
# Usa uma construção multi-estágio para criar o projeto Laravel sem precisar do Composer localmente.

# --- Estágio 1: Builder ---
# Usa a imagem do Composer para criar o projeto Laravel em um diretório temporário.
FROM composer:2.5 as builder
WORKDIR /app
# O comando abaixo garante que a versão mais recente e estável do Laravel seja instalada.
RUN composer create-project --prefer-dist laravel/laravel .

# --- Estágio 2: Aplicação Final ---
# Atualizado para usar a imagem do PHP 8.4 com FPM.
FROM php:8.4-fpm

# Define o diretório de trabalho
WORKDIR /var/www/html

# Instala dependências do sistema necessárias para as extensões do PHP
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libzip-dev \
    # Dependência para a extensão Redis
    libhiredis-dev

# Limpa o cache do apt
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instala as extensões do PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql zip bcmath gd

RUN docker-php-ext-configure pcntl --enable-pcntl \
    && docker-php-ext-install pcntl

# Instala a extensão Redis
RUN pecl install -o -f redis \
    && docker-php-ext-enable redis
# Instala o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copia os arquivos da aplicação
COPY --from=builder /app .

# **INÍCIO DA ALTERAÇÃO**
# Copia o arquivo .env para que os comandos artisan possam conectar-se à base de dados
COPY .env.example .env

# Gera a chave da aplicação
RUN php artisan key:generate

# Instala o Telescope e executa as migrações
# O --force é usado para executar as migrações em ambiente de produção/não interativo
#RUN php artisan migrate --force
#RUN php artisan telescope:install
# **FIM DA ALTERAÇÃO**

# Define as permissões corretas para o diretório de armazenamento e cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expõe a porta 9000 e inicia o PHP-FPM
EXPOSE 9000
CMD ["php-fpm"]
