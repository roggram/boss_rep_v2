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
		// セッションはSessionMiddlewareで開始済み

		// ログアウト前のユーザー情報をログに記録
		$user_id = $_SESSION['user_id'] ?? 'unknown';
		// $this->logger->info("ログアウト実行: user_id={$user_id}");

		// セッション変数を全て削除
		$_SESSION = [];

		// セッションクッキーも削除
		if (isset($_COOKIE[session_name()])) {
			setcookie(session_name(), '', time() - 3600, '/');
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
