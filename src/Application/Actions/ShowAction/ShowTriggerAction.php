<?php
declare(strict_types=1);
namespace App\Application\Actions\ShowAction;

use App\Application\Actions\Action;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Settings\SettingsInterface;
use App\Models\Trigger;


class ShowTriggerAction extends Action{
	private $twig;
	public function __construct(LoggerInterface $logger, Twig $twig, SettingsInterface $settings) {
		parent::__construct($logger, $twig, $settings);
		$this->twig = $twig;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function action(): Response {
		$template  = 'show_trigger.html.twig';
		// $this->logger->debug('Twig instance:', ['twig' => get_class($this->twig)]);
		// $this->logger->debug('Template path:', ['path' => $template]);
		// $template  = 'templates/show_trigger.html.twig';
		// $title     = 'ShowTrigger';

		// ログイン中のユーザーのトリガーのみ取得
		$user_id = $_SESSION['user_id'] ?? null;
		if (!$user_id) {
			// ログインしていない場合はログインページにリダイレクト
			return $this->response
				->withHeader('Location', '/')
				->withStatus(303);
		}

		$triggers = Trigger::where('user_id', $user_id)->get();
		// if (count($triggers) === 0){
		// }
		return $this->twig->render($this->response, $template,
			['triggers' => $triggers]);
	}
}
