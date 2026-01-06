<?php
declare(strict_types=1);
namespace App\Application\Actions\EditAction;

use App\Application\Actions\Action;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Settings\SettingsInterface;
use App\Models\Trigger;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;


class EditTriggerNameExecAction extends Action{
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
		$update_trigger_name_text = $request->getParsedBody()["update_trigger_name_text"] ?? null;
		$trigger_id = $request->getParsedBody()["trigger_id"] ?? null;

		// バリデーション
		try {
			v::notEmpty()->length(1, 20)->check($update_trigger_name_text);
		} catch (ValidationException $e) {
			$_SESSION['validation_errors'] = ['update_trigger_name_text' => ['コミュニティ名は必須で、20文字以内で入力してください']];
			$_SESSION['old_input'] = [
				'update_trigger_name_text' => $update_trigger_name_text,
				'trigger_id' => $trigger_id
			];
			return $this->response
				->withHeader('Location', "/edit_trigger_name?trigger_id={$trigger_id}")
				->withStatus(303);
		}

		// このtriggerが本当にログイン中のユーザーのものか確認（セキュリティチェック）
		$target_trigger = Trigger::where('id', $trigger_id)
			->where('user_id', $user_id)
			->first();

		if (!$target_trigger) {
			$this->logger->warning("不正なtrigger更新試行: user_id={$user_id}, trigger_id={$trigger_id}");
			return $this->response
				->withHeader("Location", "/show_trigger")
				->withStatus(303);
		}

		$target_trigger->trigger_name = $update_trigger_name_text;
		$target_trigger->save();
		return $this->response
			->withHeader("Location", "/show_trigger")
			->withStatus(303);
	}
}
