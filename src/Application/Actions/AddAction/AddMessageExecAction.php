<?php
declare(strict_types=1);
namespace App\Application\Actions\AddAction;

use App\Application\Actions\Action;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Settings\SettingsInterface;
use App\Models\Message;
use App\Models\Trigger;
use App\Models\Situation;


class AddMessageExecAction extends Action{
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
		$add_message_text = $params["add_message_text"] ?? null;
		$trigger_id = $params["trigger_id"] ?? null;
		$situation_id = $params["situation_id"] ?? null;

		// このsituationが本当にログイン中のユーザーのものか確認（セキュリティチェック）
		$situation = Situation::where('id', $situation_id)
			->where('trigger_id', $trigger_id)
			->where('user_id', $user_id)
			->first();

		if (!$situation) {
			$this->logger->warning("不正なmessage追加試行: user_id={$user_id}, trigger_id={$trigger_id}, situation_id={$situation_id}");
			return $this->response
				->withHeader("Location", "/show_trigger")
				->withStatus(303);
		}

		$message = new Message();
		$message->message = $add_message_text;
		$message->trigger_id = $trigger_id;
		$message->situation_id = $situation_id;
		$message->user_id = $user_id;  // ログイン中のユーザーIDを設定
		// created_at, deleted_atはEloquentが自動管理
		$message->save();
		return $this->response
			->withHeader("Location", "/edit_message?trigger_id={$trigger_id}&situation_id={$situation_id}")
			->withStatus(303);
	}
}
