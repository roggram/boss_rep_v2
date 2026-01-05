<?php

declare(strict_types=1);

namespace App\Application\Actions\LoginAction;

use App\Application\Actions\Action;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Settings\SettingsInterface;
use App\Models\User;
use App\Application\Helpers\SessionHelper;

class GuestLoginAction extends Action
{
    public function __construct(LoggerInterface $logger, SettingsInterface $settings)
    {
        parent::__construct($logger, $settings);
    }

    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        // ゲストユーザーをDBから取得
        $guestUser = User::where('line_id', 'guest_account')->first();

        if (!$guestUser) {
            $this->logger->error("ゲストユーザーがデータベースに存在しません");
            return $this->response
                ->withHeader('Location', '/')
                ->withStatus(302);
        }

        // セッションにユーザー情報を保存
        $_SESSION['user_id'] = $guestUser->id;
        $_SESSION['line_id'] = $guestUser->line_id;
        $_SESSION['user_name'] = $guestUser->user_name;

        $this->logger->info("ゲストログイン成功: user_id={$guestUser->id}");

        // セッション固定攻撃対策：ログイン成功後にセッションIDを再生成
        SessionHelper::safeRegenerateId();

        // トリガー一覧ページにリダイレクト
        return $this->response
            ->withHeader('Location', '/show_trigger')
            ->withStatus(302);
    }
}
