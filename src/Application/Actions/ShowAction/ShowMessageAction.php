<?php
declare(strict_types=1);
namespace App\Application\Actions\ShowAction;

use App\Application\Actions\Action;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Settings\SettingsInterface;
use App\Models\Message;
use App\Models\Situation;


class ShowMessageAction extends Action{
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
			// 他のユーザーのsituationにアクセスしようとした場合
			$this->logger->warning("不正アクセス試行: user_id={$user_id}, trigger_id={$trigger_id}, situation_id={$situation_id}");
			return $this->response
				->withHeader('Location', '/show_trigger')
				->withStatus(303);
		}

		// ログイン中のユーザーのmessageのみ取得
		$template  = './show_message.html.twig';
		$messages = Message::query()
			->where("trigger_id", $trigger_id)
			->where("situation_id", $situation_id)
			->where("user_id", $user_id)
			->get();
		return $this->twig->render($this->response, $template,
			[ 'trigger_id' => $trigger_id,
				'situation_id' => $situation_id,
				"messages" => $messages]);
	}
}
