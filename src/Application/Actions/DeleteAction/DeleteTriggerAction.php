<?php
declare(strict_types=1);
namespace App\Application\Actions\DeleteAction;

use App\Application\Actions\Action;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Settings\SettingsInterface;
use App\Models\Trigger;
use App\Models\Situation;
use App\Models\Message;


class DeleteTriggerAction extends Action{
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

		$request = $this->request;
		$trigger_id = $request->getParsedBody()["trigger_id"] ?? null;

		// このtriggerが本当にログイン中のユーザーのものか確認（セキュリティチェック）
		$target_trigger = Trigger::where('id', $trigger_id)
			->where('user_id', $user_id)
			->first();

		if (!$target_trigger) {
			$this->logger->warning("不正なtrigger削除試行: user_id={$user_id}, trigger_id={$trigger_id}");
			return $this->response
				->withHeader("Location", "/show_trigger")
				->withStatus(303);
		}

		$target_trigger->delete();
		// 関連するsituationsとmessagesも削除（ユーザーのものだけ）
		$target_situations = Situation::query()
			->where("trigger_id", $trigger_id)
			->where("user_id", $user_id)
			->delete();
		$this->logger->info("Deleted situations for trigger_id: {$trigger_id}, count: {$target_situations}");
		$target_messages = Message::query()
			->where("trigger_id", $trigger_id)
			->where("user_id", $user_id)
			->delete();
		$this->logger->info("Deleted messages for trigger_id: {$trigger_id}, count: {$target_messages}");
		return $this->response
			->withHeader("Location", "/show_trigger")
			->withStatus(303);
	}
}
