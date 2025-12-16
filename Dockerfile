FROM php:8.1-alpine

# 必要なパッケージとPHP拡張機能をインストール
RUN apk add --no-cache \
    # MySQLクライアントライブラリ
    mysql-client \
    # ビルドに必要な依存関係
    autoconf \
    g++ \
    make

# PDO MySQL拡張機能をインストール
RUN docker-php-ext-install pdo pdo_mysql

# 作業ディレクトリを設定
WORKDIR /var/www

# ビルド依存関係をクリーンアップ（イメージサイズ削減）
RUN apk del autoconf g++ make

# PHPビルトインサーバーを起動
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
