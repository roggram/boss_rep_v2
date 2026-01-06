<?php
declare(strict_types=1);
namespace App\Application\Actions\AddAction;

use App\Application\Actions\Action;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Settings\SettingsInterface;
use App\Models\Trigger;


class AddTriggerAction extends Action{
	private $twig;
	public function __construct(LoggerInterface $logger, Twig $twig, SettingsInterface $settings) {
		parent::__construct($logger, $twig, $settings);
		$this->twig = $twig;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function action(): Response {
		// ログイン確認
		$user_id = $_SESSION['user_id'] ?? null;
		if (!$user_id) {
			return $this->response
				->withHeader('Location', '/')
				->withStatus(303);
		}

		// バリデーションエラーと旧入力値を取得
		$validation_errors = $_SESSION['validation_errors'] ?? [];
		$old_input = $_SESSION['old_input'] ?? [];
		// セッションから削除（一度だけ表示）
		unset($_SESSION['validation_errors']);
		unset($_SESSION['old_input']);

		$template  = 'add_trigger.html.twig';
		return $this->twig->render($this->response, $template,
			[
				'validation_errors' => $validation_errors,
				'old_input' => $old_input
			]);
	}
}
