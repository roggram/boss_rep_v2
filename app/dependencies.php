<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use Illuminate\Database\Capsule\Manager as Capsule;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },
        Twig::class => function (ContainerInterface $container) {
            // Twig設定
            $settings = $container->get(SettingsInterface::class);
            // テンプレートの置き場所設定
            $renderTwig = Twig::create(__DIR__ . '/../templates', $settings->get('twig'));
            // Css/Jsのキャッシュ読み込み (hoge.css?v=xxxx 対応)
            $renderTwig->offsetSet('reloadCache', $settings->get('twig')['reloadCache']);
            // CSS/JSなどのファイル格納
            $renderTwig->offsetSet('assetsUrl', $settings->get('assets')['path']);
            return $renderTwig;
        },

        Capsule::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);
            $eloquent = new Capsule;
            $dbSettings = $settings->get('db');
            $eloquent->addConnection($dbSettings);
            // Make this Capsule instance available globally via static methods
            $eloquent->setAsGlobal();
            // Setup the Eloquent ORM...
            $eloquent->bootEloquent();

            return $eloquent;
        }
    ]);
    //コントローラから、$this->container['logger']->debug()といった形で呼び出すため、インスタンス情報を取得する
    // https://www.rinsymbol.net/entry/2018/05/04/013336#DB%E3%83%86%E3%83%BC%E3%83%96%E3%83%AB%E3%81%A8%E3%83%A2%E3%83%87%E3%83%AB%E3%82%AF%E3%83%A9%E3%82%B9%E3%81%AE%E4%BD%9C%E6%88%90
    // 上の記事で以下二行を書いた
    // DIC configuration
    // $container = $app->getContainer();
    // $this->container->get('logger');
    // $this->container->get('db');

    // twigモジュールを入れている場合
    // $this->container->get('view');
};
