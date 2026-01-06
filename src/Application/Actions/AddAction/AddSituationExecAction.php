<?php
declare(strict_types=1);
namespace App\Application\Actions\AddAction;

use App\Application\Actions\Action;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Settings\SettingsInterface;
use App\Models\Situation;
use App\Models\Trigger;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;


class AddSituationExecAction extends Action{
	private $twig;
	public function __construct(LoggerInterface $logger, Twig $twig, SettingsInterface $settings) {
		parent::__construct($logger, $twig, $settings);
		$this->twig = $twig;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function action(): Response {
		$request = $this->request;
		$response = $this->response;

		// ログイン確認
		$user_id = $_SESSION['user_id'] ?? null;
		if (!$user_id) {
			return $this->response
				->withHeader('Location', '/')
				->withStatus(303);
		}

		$params = $request->getParsedBody();
		// リクエストパラメータ
		$situation_name = $params["add_situation_name"] ?? null;
		$trigger_id = $params["trigger_id"] ?? null;

		// バリデーション
		try {
			v::notEmpty()->length(1, 40)->check($situation_name);
		} catch (ValidationException $e) {
			$_SESSION['validation_errors'] = ['add_situation_name' => ['メンバー名は必須で、40文字以内で入力してください']];
			$_SESSION['old_input'] = ['add_situation_name' => $situation_name];
			return $response
				->withHeader('Location', "/show_situation?trigger_id={$trigger_id}")
				->withStatus(303);
		}

		// このtriggerが本当にログイン中のユーザーのものか確認（セキュリティチェック）
		$trigger = Trigger::where('id', $trigger_id)
			->where('user_id', $user_id)
			->first();

		if (!$trigger) {
			$this->logger->warning("不正なsituation追加試行: user_id={$user_id}, trigger_id={$trigger_id}");
			return $response
				->withHeader("Location", "/show_trigger")
				->withStatus(303);
		}

		$situation = new Situation();
		$situation->situation_name = $situation_name;
		$situation->trigger_id = $trigger_id;
		$situation->user_id = $user_id;  // ログイン中のユーザーIDを設定
		// created_at, deleted_atはEloquentが自動管理
		$situation->save();
		return $response
			->withHeader("Location", "/show_situation?trigger_id={$trigger_id}")
			->withStatus(303);
	}
}
