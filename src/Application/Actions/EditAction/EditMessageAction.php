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
use App\Models\Situation;


class EditMessageAction extends Action{
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
		$trigger_id = $request->getQueryParams()["trigger_id"] ?? null;
		$situation_id = $request->getQueryParams()["situation_id"] ?? null;

		// パラメータ確認
		if (!$trigger_id || !$situation_id) {
			return $this->response
				->withHeader('Location', '/show_trigger')
				->withStatus(303);
		}

		// このsituationが本当にログイン中のユーザーのものか確認（セキュリティチェック）
		$situation = Situation::where('id', $situation_id)
			->where('trigger_id', $trigger_id)
			->where('user_id', $user_id)
			->first();

		if (!$situation) {
			$this->logger->warning("不正アクセス試行: user_id={$user_id}, trigger_id={$trigger_id}, situation_id={$situation_id}");
			return $this->response
				->withHeader('Location', '/show_trigger')
				->withStatus(303);
		}

		$template  = 'edit_message.html.twig';
		$messages = Message::query()
			->where("trigger_id", $trigger_id)
			->where("situation_id", $situation_id)
			->where("user_id", $user_id)
			->orderBy('display_order')
			->get();

		// バリデーションエラーと旧入力値を取得
		$validation_errors = $_SESSION['validation_errors'] ?? [];
		$old_input = $_SESSION['old_input'] ?? [];
		// セッションから削除（一度だけ表示）
		unset($_SESSION['validation_errors']);
		unset($_SESSION['old_input']);

		return $this->twig->render($this->response, $template,
		[
			'trigger_id' => $trigger_id,
			'situation_id' => $situation_id,
			'messages' => $messages,
			'validation_errors' => $validation_errors,
			'old_input' => $old_input
		]);
	}
}
