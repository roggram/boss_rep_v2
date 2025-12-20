<?php

declare(strict_types=1);

use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {

    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () {
            return new Settings([
                'displayErrorDetails' => true, // 一時的にtrueでエラー詳細を確認
                'logError'            => true,
                'logErrorDetails'     => true,
                'logger' => [
                    'name' => 'slim-app',
                    'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                    'level' => Logger::DEBUG,
                ],
                // Twig設定
                'twig' => [
                    'reloadCache'      => time(),
                    'strict_variables' => true
                    //    ,'cache'            => __DIR__ . '/../var/cache/twig'
                    ,
                    'cache'            => false
                ]
                // CSS,JS,画像置き場
                ,
                'assets' => [
                    'path'              => '/assets'
                ],
                'db' => [
                    'driver' => 'mysql',
                    'host' => $_ENV['DB_HOST'],
                    'database' => $_ENV['DB_NAME'],
                    'username' => $_ENV['DB_USER'],
                    'password' => $_ENV['DB_PASSWORD'],
                    'charset'   => 'utf8',
                    'collation' => 'utf8_unicode_ci',
                    'prefix'    => '',
                ]
            ]);
        }
    ]);
};
