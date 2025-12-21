<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class SessionMiddleware implements Middleware
{
    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        // セッションが既に開始されていない場合のみ開始
        if (session_status() === PHP_SESSION_NONE) {
            // セッション保存パスを設定
            $session_path = sys_get_temp_dir();
            if (!is_writable($session_path)) {
                $session_path = '/tmp';
            }
            session_save_path($session_path);

            // セッションクッキーの設定（セキュリティとセッション維持のため）
            ini_set('session.cookie_lifetime', '3600');     // クッキー有効期限を1時間に設定
            ini_set('session.gc_maxlifetime', '3600');      // セッションデータの有効期限を1時間に設定
            ini_set('session.cookie_samesite', 'Lax');      // LINEからのリダイレクトでもクッキー送信
            ini_set('session.cookie_httponly', '1');        // XSS対策
            ini_set('session.cookie_secure', '0');          // HTTPSの場合は '1' に変更
            ini_set('session.use_only_cookies', '1');       // URLパラメータでのセッションID送信を無効化
            ini_set('session.use_strict_mode', '1');        // セッション固定攻撃対策
            ini_set('session.cookie_path', '/');            // クッキーのパスを明示的に設定

            // セッション開始
            session_start();
        }

        // セッションをリクエスト属性として追加
        $request = $request->withAttribute('session', $_SESSION);

        return $handler->handle($request);
    }
}
