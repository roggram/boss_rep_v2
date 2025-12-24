<?php
declare(strict_types=1);
namespace App\Application\Actions\AddAction;

use App\Application\Actions\Action;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Settings\SettingsInterface;
use App\Models\Trigger;


class AddTriggerExecAction extends Action{
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

		$params = $this->request->getParsedBody();
		// リクエストパラメータ
		$trigger_name = $params["trigger_name"] ?? null;
		$trigger = new Trigger();
		$trigger->trigger_name = $trigger_name;
		$trigger->user_id = $user_id;  // ログイン中のユーザーIDを設定
		// created_at, deleted_atはEloquentが自動管理
		$trigger->save();
		return $this->response
			->withHeader("Location", "/show_trigger")
			->withStatus(303);
	}
}
