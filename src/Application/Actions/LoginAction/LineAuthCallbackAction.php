<?php

declare(strict_types=1);

namespace App\Application\Actions\LoginAction;

use App\Application\Actions\Action;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Application\Settings\SettingsInterface;
use Illuminate\Support\Facades\Redis;
use App\Models\User;


class LineAuthCallbackAction extends Action
{
	private const LINE_WEB_LOGIN_STATE = 'line_web_login_state';
	// private $channel_id;
	// private $redirect_uri;
	private $twig;
	public function __construct(LoggerInterface $logger, SettingsInterface $settings, Twig $twig)
	{
		parent::__construct($logger, $settings);
		$this->logger = $logger;
		$this->twig = $twig;
		// $this->channel_id = $_ENV['LINE_CHANNEL_ID'];
		// $this->redirect_uri = $_ENV['LINE_AUTH_REDIRECT_URI'];
	}

	/**
	 * {@inheritdoc}
	 */
	protected function action(): Response
	{
		return $this->auth($this->request, $this->response);
	}

	// ここのタスクに関する公式解説URL
	// https: developers.line.biz/ja/docs/line-login/integrate-line-login#receiving-the-authorization-code-or-error-response-with-a-web-app
	private function auth(Request $request, Response $response): Response
	{
		// セッションはSessionMiddlewareで開始済み
		$params = $request->getQueryParams();
		// -+-+-+-+-+-+-+-+--+--+-
		// 正常の場合のパラメータ取得
		// -+-+-+-+-+-+-+-+--+--+-
		$code = $params['code'] ?? null;
		$state = $params['state'] ?? null;
		if (!isset($_SESSION['LINE_LOGIN_STATE'])) {
			$this->logger->error("セッションにLINE_LOGIN_STATEが存在しません。");
			return $this->twig->render($response, "error.html.twig", [
				'error_message' => "セッションが無効です。再度ログインしてください。"
			]);
		}
		$friendship_status_changed = $params['friendship_status_changed'] ?? null;
		// -+-+-+-+-+-+-+-+--+--+-
		// エラー時のパラメータがあれば対応
		// -+-+-+-+-+-+-+-+--+--+-
		if (isset($params['error']) || isset($params['error_description'])) {
			// エラーがあればエラーページにリダイレクト
			return $this->twig->render(
				$response,
				"error.html.twig",
				['error' => ""]
			);
		}
		// 作成したstateとコールバックURLのstateが一致しない場合はエラー
		if ($state !== $_SESSION["LINE_LOGIN_STATE"]) {
			return $this->twig->render(
				$response,
				"error.html.twig",
				['error' => ""]
			);
		}
		unset($_SESSION["LINE_LOGIN_STATE"]);
		// -+-+-+-+-+-+-+-+--+--+-
		//  curlでアクセストークンを取得
		// -+-+-+-+-+-+-+-+--+--+-
		// tokenをリクエストするURLを作成
		$token_request_url = http_build_query([
			'grant_type' => 'authorization_code',
			'code' => $code,
			'redirect_uri' => $_ENV['LINE_AUTH_REDIRECT_URI'],
			'client_id' => $_ENV['LINE_CHANNEL_ID'],
			'client_secret' => $_ENV['LINE_CHANNEL_SECRET']
		]);
		$ch = curl_init();
		curl_setopt_array($ch, [
			// curlで叩きに行くURLを設定
			CURLOPT_URL => "https://api.line.me/oauth2/v2.1/token",
			CURLOPT_POST => true,
			// レスポンスを文字列で返す
			CURLOPT_RETURNTRANSFER => true,
			// ヘッダー情報を設定
			CURLOPT_HTTPHEADER => ["Content-Type: application/x-www-form-urlencoded"],
			// POSTリクエストで送信するデータを指定
			CURLOPT_POSTFIELDS => $token_request_url,
		]);
		// curl_exec($ch)でリクエストを実行
		$response_from_line_token_server = curl_exec($ch);
		curl_close($ch);
		// -+-+-+-+-+-+-+-+--+--+-
		// curl終了
		// -+-+-+-+-+-+-+-+--+--+-
		if ($response_from_line_token_server === false) { // curlによるtokenリクエストが失敗した場合
			$this->logger->error("LINE APIへのトークンリクエストが失敗しました（curl_exec error）");
			return $this->twig->render(
				$response,
				"error.html.twig",
				['error_message' => "curl_execが失敗しました"]
			);
		}
		// レスポンスをjson形式に変換
		$response_from_line_token_server_assoc = json_decode($response_from_line_token_server, true);
		if ($response_from_line_token_server_assoc === null) // もしjson_decodeが失敗した場合
		{
			$this->logger->error("LINEからのアクセスtokenを含むレスポンスのjson_decode()に失敗しました");
			return $this->twig->render(
				$response,
				"error.html.twig",
				['error_message' => "LINEログインに失敗しました"]
			);
		}
		// アクセストークンを取り出す
		$access_token = $response_from_line_token_server_assoc['access_token'] ?? null;
		if (!$access_token) {
			$this->logger->error("アクセストークンが取得できませんでした");
			return $this->twig->render(
				$response,
				"error.html.twig",
				['error_message' => "LINEログインに失敗しました"]
			);
		}

		// -+-+-+-+-+-+-+-+--+--+-
		// アクセストークンを使ってユーザー情報を取得
		// -+-+-+-+-+-+-+-+--+--+-
		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => "https://api.line.me/v2/profile",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => [
				"Authorization: Bearer {$access_token}"
			],
		]);
		$profile_response = curl_exec($ch);
		curl_close($ch);

		if ($profile_response === false) {
			$this->logger->error("LINEプロフィール情報の取得に失敗しました");
			return $this->twig->render(
				$response,
				"error.html.twig",
				['error_message' => "ユーザー情報の取得に失敗しました"]
			);
		}

		$profile = json_decode($profile_response, true);
		if ($profile === null) {
			$this->logger->error("LINEプロフィール情報のjson_decode()に失敗しました");
			return $this->twig->render(
				$response,
				"error.html.twig",
				['error_message' => "ユーザー情報の取得に失敗しました"]
			);
		}

		// -+-+-+-+-+-+-+-+--+--+-
		// ユーザー情報をDBに保存（既存ユーザーか新規ユーザーか判定）
		// -+-+-+-+-+-+-+-+--+--+-
		$line_id = $profile['userId'] ?? null;
		$user_name = $profile['displayName'] ?? 'ゲスト';

		if (!$line_id) {
			$this->logger->error("LINE IDが取得できませんでした");
			return $this->twig->render(
				$response,
				"error.html.twig",
				['error_message' => "ユーザー情報の取得に失敗しました"]
			);
		}

		// 既存ユーザーを検索
		$user = User::where('line_id', $line_id)->first();

		if (!$user) {
			// 新規ユーザー登録
			$user = User::create([
				'line_id' => $line_id,
				'user_name' => $user_name
			]);
			$this->logger->info("新規ユーザー登録: user_id={$user->id}, line_id={$line_id}");
		} else {
			// 既存ユーザーの名前を更新（LINEで名前が変わっている可能性があるため）
			$user->user_name = $user_name;
			$user->save();
			$this->logger->info("既存ユーザーログイン: user_id={$user->id}, line_id={$line_id}");
		}

		// -+-+-+-+-+-+-+-+--+--+-
		// セッションにユーザーIDを保存（ログイン状態を維持）
		// -+-+-+-+-+-+-+-+--+--+-
		$_SESSION['user_id'] = $user->id;
		$_SESSION['line_id'] = $line_id;
		$_SESSION['user_name'] = $user_name;

		$this->logger->info("ログイン成功: user_id={$user->id}");

		// ログイン後のページにリダイレクト
		return $this->response
			->withHeader("Location", "/show_trigger")
			->withStatus(303);
	}
}
