FROM php:8.1-alpine

# 必要なパッケージとPHP拡張機能をインストール
RUN apk add --no-cache \
    mysql-client \
    autoconf \
    g++ \
    make \
    git \
    unzip

# Composerをインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# PDO MySQL拡張機能をインストール
RUN docker-php-ext-install pdo pdo_mysql

# 作業ディレクトリを設定
WORKDIR /var/www

# composer.jsonとcomposer.lockをコピー
COPY composer.json composer.lock ./

# 依存関係をインストール
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 残りのアプリケーションファイルをコピー
COPY . .

# ビルド依存関係をクリーンアップ
RUN apk del autoconf g++ make

# PHPビルトインサーバーを起動
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
