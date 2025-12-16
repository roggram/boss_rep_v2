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


class EditSituationNameExecAction extends Action{
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
		$update_situation_name_text = $request->getParsedBody()["update_situation_name_text"] ?? null;
		$trigger_id = $request->getParsedBody()["trigger_id"] ?? null;
		$situation_id = $request->getParsedBody()["situation_id"] ?? null;

		// このsituationが本当にログイン中のユーザーのものか確認（セキュリティチェック）
		$target_situation = Situation::where('id', $situation_id)
			->where('trigger_id', $trigger_id)
			->where('user_id', $user_id)
			->first();

		if (!$target_situation) {
			$this->logger->warning("不正なsituation更新試行: user_id={$user_id}, trigger_id={$trigger_id}, situation_id={$situation_id}");
			return $this->response
				->withHeader("Location", "/show_trigger")
				->withStatus(303);
		}

		$target_situation->situation_name = $update_situation_name_text;
		$target_situation->save();
		return $this->response
			->withHeader("Location", "/show_situation?trigger_id={$trigger_id}")
			->withStatus(303);
	}
}
