<?php

declare(strict_types=1);

namespace App\Application\Actions\LoginAction;

use App\Application\Actions\Action;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Settings\SettingsInterface;


class LineLoginAction extends Action
{
	private $twig;
	private $channel_id;
	private $redirect_uri;

	public function __construct(LoggerInterface $logger, SettingsInterface $settings)
	{
		parent::__construct($logger, $settings);
		$this->channel_id = $_ENV['LINE_CHANNEL_ID'];
		$this->redirect_uri = $_ENV['LINE_AUTH_REDIRECT_URI'];
	}

	/**
	 * {@inheritdoc}
	 */
	protected function action(): Response
	{
		return $this->goToAuthPage();
	}

	protected function goToAuthPage()
	{
		/* ==========================================
		state :
			(LINE公式より) CSRF防止用の固有な英数字の文字列。
			 ログインセッションごとにウェブアプリでランダムに生成してください。
			 なお、URLエンコードされた文字列は使用できません。
		nonce :
			(LINE公式より) リプレイアタック (opens new window)を防止するための文字列。
			この値はレスポンスで返されるIDトークンに含まれます。
		===========================================*/
		// セッションはSessionMiddlewareで開始済み
		// stateとnonceを作成
		$state = $this->generateToken();
		$nonce = $this->generateToken();
		// stateとnonceをセッションに保存
		$_SESSION['LINE_LOGIN_STATE'] = $state;
		$_SESSION['LINE_LOGIN_NONCE'] = $nonce;
		// 認可URLの作成
		$url_to_line_approval_server = $this->generateAuthUrl($state, $nonce);
		// LINE認証認可サーバーURLにリダイレクト
		return $this->response
			->withHeader('Location', $url_to_line_approval_server)
			->withStatus(302);
	}

	private function generateToken($length = 32)
	{
		return bin2hex(random_bytes($length));
	}

	private function generateAuthUrl($state, $nonce)
	{
		// 2. redirect_uri: (LINE公式より)LINE Developersコンソールに登録したコールバックURLをURLエンコードした文字列。
		//    任意のクエリパラメータを付与できます。
		// 3. scope: (LINE公式より)ユーザーに付与を依頼する権限。詳しくは、「スコープ」を参照してください。
		// パラメータ詳細は、URLで確認　https://developers.line.biz/ja/docs/line-login/integrate-line-login/#making-an-authorization-request
		$params = [
			'response_type' => 'code',
			'client_id' => $this->channel_id,
			'redirect_uri' => $this->redirect_uri,
			'state' => $state,
			'scope' => 'profile openid',
			'nonce' => $nonce
		];
		$query_params = http_build_query($params);
		return "https://access.line.me/oauth2/v2.1/authorize?{$query_params}";
	}
}
