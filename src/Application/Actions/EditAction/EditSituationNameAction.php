<?php
declare(strict_types=1);
namespace App\Application\Actions\EditAction;

use App\Application\Actions\Action;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Settings\SettingsInterface;
use App\Models\Situation;
use App\Models\Trigger;


class EditSituationNameAction extends Action{
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
		$trigger_id = $request->getQueryParams()["trigger_id"] ?? null;

		// ログイン確認
		$user_id = $_SESSION['user_id'] ?? null;
		if (!$user_id) {
			return $this->response
				->withHeader('Location', '/')
				->withStatus(303);
		}

		// パラメータ確認
		if (!$trigger_id) {
			return $this->response
				->withHeader('Location', '/show_trigger')
				->withStatus(303);
		}

		// このtriggerが本当にログイン中のユーザーのものか確認（セキュリティチェック）
		$trigger = Trigger::where('id', $trigger_id)
			->where('user_id', $user_id)
			->first();

		if (!$trigger) {
			$this->logger->warning("不正アクセス試行: user_id={$user_id}, trigger_id={$trigger_id}");
			return $this->response
				->withHeader('Location', '/show_trigger')
				->withStatus(303);
		}

		$template  = 'edit_situation.html.twig';
		$situations = Situation::query()
			->where("trigger_id", $trigger_id)
			->where("user_id", $user_id)
			->get();
		return $this->twig->render($this->response, $template,
			[ 'trigger_id' => $trigger_id, 'situations' => $situations]);
	}
}
