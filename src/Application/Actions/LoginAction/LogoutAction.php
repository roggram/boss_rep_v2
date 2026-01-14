<?php

declare(strict_types=1);

namespace App\Application\Actions\LoginAction;

use App\Application\Actions\Action;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Settings\SettingsInterface;


class LogoutAction extends Action
{
	public function __construct(LoggerInterface $logger, SettingsInterface $settings)
	{
		parent::__construct($logger, $settings);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function action(): Response
	{
		return $this->logout();
	}

	protected function logout(): Response
	{
		// SessionMiddleware経由でセッション全体を取得
		$session = $this->request->getAttribute('session') ?? [];
		$user_id = $session['user_id'] ?? 'unknown';

		// ログアウト前のユーザー情報をログに記録
		$this->logger->info("ログアウト実行: user_id={$user_id}");

		// セッション変数を全て削除（PHP公式マニュアル推奨）
		$_SESSION = [];

		// セッションクッキーを削除
		if (ini_get('session.use_cookies')) {
			$params = session_get_cookie_params();
			setcookie(
				session_name(),
				'',
				time() - 42000,
				$params['path'],
				$params['domain'],
				$params['secure'],
				$params['httponly']
			);
		}

		// セッションを破棄
		session_destroy();

		$this->logger->info("ログアウト完了");

		// ログインページにリダイレクト
		return $this->response
			->withHeader('Location', '/')
			->withStatus(303);
	}
}
