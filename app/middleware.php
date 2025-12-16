<?php

declare(strict_types=1);

use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

use App\Application\Middleware\SessionMiddleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\App;

return function (App $app) {
    $app->add(SessionMiddleware::class);
    $app->add(TwigMiddleware::createFromContainer($app, Twig::class));
    // $app->add(function (Request $request, RequestHandler $handler) {
    //     $response = $handler->handle($request);
    //     return $response->withHeader('Content-Type', 'application/json')
    //                     ->withHeader('Access-Control-Allow-Origin', 'http://localhost:8080');
    //   });
};
