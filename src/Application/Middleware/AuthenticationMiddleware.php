<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class AuthenticationMiddleware implements Middleware
{
    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        // セッションからuser_idを取得
        $user_id = $_SESSION['user_id'] ?? null;

        // 未ログインの場合はログインページにリダイレクト
        if (!$user_id) {
            $response = new SlimResponse();
            return $response
                ->withHeader('Location', '/')
                ->withStatus(303);
        }

        // 認証済みの場合、user_idをリクエスト属性として追加
        $request = $request->withAttribute('user_id', $user_id);

        // 拡張されたrequestを次の処理へ
        return $handler->handle($request);
    }
}
