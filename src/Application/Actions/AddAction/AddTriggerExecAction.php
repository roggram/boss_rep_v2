<?php
declare(strict_types=1);
namespace App\Application\Actions\AddAction;

use App\Application\Actions\Action;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Settings\SettingsInterface;
use App\Models\Trigger;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;


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
		// 認証済みユーザーIDを取得
		$user_id = $this->request->getAttribute('user_id');

		$params = $this->request->getParsedBody();
		// リクエストパラメータ
		$trigger_name = $params["trigger_name"] ?? null;

		// バリデーション
		try {
			v::notEmpty()->length(1, 20)->check($trigger_name);
		} catch (ValidationException $e) {
			$_SESSION['validation_errors'] = ['trigger_name' => ['コミュニティ名は必須で、20文字以内で入力してください']];
			$_SESSION['old_input'] = ['trigger_name' => $trigger_name];
			return $this->response
				->withHeader('Location', '/add_trigger')
				->withStatus(303);
		}

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
