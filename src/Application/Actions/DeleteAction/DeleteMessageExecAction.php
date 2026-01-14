<?php
declare(strict_types=1);
namespace App\Application\Actions\DeleteAction;

use App\Application\Actions\Action;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Settings\SettingsInterface;
use App\Models\Message;


class DeleteMessageExecAction extends Action{
	private $twig;
	public function __construct(LoggerInterface $logger, Twig $twig, SettingsInterface $settings) {
		parent::__construct($logger, $twig, $settings);
		$this->twig = $twig;
	}
	
	/**
	 * {@inheritdoc}
	 */
	protected function action(): Response {
		// 認証済みユーザーIDを取得
		$user_id = $this->request->getAttribute('user_id');
		$request = $this->request;
		$message_id = $request->getParsedBody()["message_id"] ?? null;

		// このmessageが本当にログイン中のユーザーのものか確認（セキュリティチェック）
		$target_message = Message::where('id', $message_id)
			->where('user_id', $user_id)
			->first();

		if (!$target_message) {
			$this->logger->warning("不正なmessage削除試行: user_id={$user_id}, message_id={$message_id}");
			return $this->response
				->withHeader("Location", "/show_trigger")
				->withStatus(303);
		}

		$trigger_id = $target_message->trigger_id;
		$situation_id = $target_message->situation_id;
		$target_message->delete();
		return $this->response
			->withHeader("Location", "/edit_message?trigger_id={$trigger_id}&situation_id={$situation_id}")
			->withStatus(303);
	}
}
