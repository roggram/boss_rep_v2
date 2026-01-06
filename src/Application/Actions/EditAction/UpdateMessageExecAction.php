<?php
declare(strict_types=1);
namespace App\Application\Actions\EditAction;

use App\Application\Actions\Action;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Settings\SettingsInterface;
use App\Models\Message;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;


class UpdateMessageExecAction extends Action{
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
		$update_message_text = $request->getParsedBody()["update_message_text"] ?? null;
		$message_id = $request->getParsedBody()["message_id"] ?? null;

		// バリデーション
		try {
			v::notEmpty()->length(1, 2048)->check($update_message_text);
		} catch (ValidationException $e) {
			// リダイレクト先取得のため、メッセージを取得
			$target_message = Message::where('id', $message_id)
				->where('user_id', $user_id)
				->first();

			if ($target_message) {
				$trigger_id = $target_message->trigger_id;
				$situation_id = $target_message->situation_id;
			} else {
				// メッセージが見つからない場合はshow_triggerへ
				return $this->response
					->withHeader("Location", "/show_trigger")
					->withStatus(303);
			}

			$_SESSION['validation_errors'] = ['update_message_text' => ['メッセージは必須で、2048文字以内で入力してください']];
			$_SESSION['old_input'] = [
				'update_message_text' => $update_message_text,
				'message_id' => $message_id
			];
			return $this->response
				->withHeader('Location', "/edit_message?trigger_id={$trigger_id}&situation_id={$situation_id}")
				->withStatus(303);
		}

		// このmessageが本当にログイン中のユーザーのものか確認（セキュリティチェック）
		$target_message = Message::where('id', $message_id)
			->where('user_id', $user_id)
			->first();

		if (!$target_message) {
			$this->logger->warning("不正なmessage更新試行: user_id={$user_id}, message_id={$message_id}");
			return $this->response
				->withHeader("Location", "/show_trigger")
				->withStatus(303);
		}

		$target_message->message = $update_message_text;
		$target_message->save();
		$trigger_id = $target_message->trigger_id;
		$situation_id = $target_message->situation_id;
		return $this->response
			->withHeader("Location", "/edit_message?trigger_id={$trigger_id}&situation_id={$situation_id}")
			->withStatus(303);
	}
}
