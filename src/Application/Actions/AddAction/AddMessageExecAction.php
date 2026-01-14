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
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;


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
		// 認証済みユーザーIDを取得
		$user_id = $this->request->getAttribute('user_id');

		$params = $this->request->getParsedBody();
		$add_message_text = $params["add_message_text"] ?? null;
		$trigger_id = $params["trigger_id"] ?? null;
		$situation_id = $params["situation_id"] ?? null;

		// バリデーション
		try {
			v::notEmpty()->length(1, 2048)->check($add_message_text);
		} catch (ValidationException $e) {
			$_SESSION['validation_errors'] = ['add_message_text' => ['メッセージは必須で、2048文字以内で入力してください']];
			$_SESSION['old_input'] = ['add_message_text' => $add_message_text];
			return $this->response
				->withHeader('Location', "/edit_message?trigger_id={$trigger_id}&situation_id={$situation_id}")
				->withStatus(303);
		}

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
