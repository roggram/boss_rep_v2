<?php
declare(strict_types=1);
namespace App\Application\Actions\EditAction;

use App\Application\Actions\Action;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Settings\SettingsInterface;
use App\Models\Message;


class UpdateMessageOrderAction extends Action{
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

		$params = $this->request->getParsedBody();
		$message_id = $params["message_id"] ?? null;
		$direction = $params["direction"] ?? null;  // "up" or "down"
		$trigger_id = $params["trigger_id"] ?? null;
		$situation_id = $params["situation_id"] ?? null;

		// パラメータ確認
		if (!$message_id || !$direction || !$trigger_id || !$situation_id) {
			return $this->response
				->withHeader('Location', '/show_trigger')
				->withStatus(303);
		}

		// 対象メッセージを取得（セキュリティチェック込み）
		$currentMessage = Message::where('id', $message_id)
			->where('user_id', $user_id)
			->first();

		if (!$currentMessage) {
			$this->logger->warning("不正な順序変更試行: user_id={$user_id}, message_id={$message_id}");
			return $this->response
				->withHeader('Location', '/show_trigger')
				->withStatus(303);
		}

		// 入れ替え対象のメッセージを取得
		if ($direction === 'up') {
			$targetMessage = Message::where('situation_id', $situation_id)
				->where('user_id', $user_id)
				->where('display_order', '<', $currentMessage->display_order)
				->orderBy('display_order', 'desc')
				->first();
		} else {
			$targetMessage = Message::where('situation_id', $situation_id)
				->where('user_id', $user_id)
				->where('display_order', '>', $currentMessage->display_order)
				->orderBy('display_order', 'asc')
				->first();
		}

		// 入れ替え対象が存在する場合、display_orderを交換
		if ($targetMessage) {
			$tempOrder = $currentMessage->display_order;
			$currentMessage->display_order = $targetMessage->display_order;
			$targetMessage->display_order = $tempOrder;
			$currentMessage->save();
			$targetMessage->save();
		}

		return $this->response
			->withHeader("Location", "/edit_message?trigger_id={$trigger_id}&situation_id={$situation_id}")
			->withStatus(303);
	}
}
