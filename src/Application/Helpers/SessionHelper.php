<?php

declare(strict_types=1);

namespace App\Application\Helpers;

/**
 * セッション管理のヘルパークラス
 *
 * 不安定なネットワーク環境でも安全に動作するセッション再生成を提供
 * PHPマニュアル推奨の実装: https://www.php.net/manual/ja/function.session-regenerate-id.php
 */
class SessionHelper
{
    /**
     * 安全にセッションIDを再生成する
     *
     * モバイルネットワークやWiFiなど不安定なネットワークでも
     * セッションが消失しないように、古いセッションを一定期間保持する
     *
     * @return void
     */
    public static function safeRegenerateId(): void
    {
        // 新しいセッションIDを生成
        $new_session_id = session_create_id();

        // 現在のセッションデータを退避（新しいセッションに引き継ぐため）
        $session_data = $_SESSION;

        // 新しいセッションIDを現在のセッションに保存
        $_SESSION['new_session_id'] = $new_session_id;

        // 現在のセッションが破棄されたタイムスタンプを記録
        // これにより、古いセッションIDでのアクセスを一定期間（300秒）許可
        $_SESSION['destroyed'] = time();

        // 現在のセッションを保存して閉じる
        session_commit();

        // 新しいセッションIDでセッションを開始
        session_id($new_session_id);

        // strict_modeを一時的に無効化して新しいセッションを作成
        // 注：session_start()後にstrict_modeを戻すことはできない（エラーになる）
        // 次のリクエストでSessionMiddlewareが自動的に'1'に戻すため問題ない
        ini_set('session.use_strict_mode', '0');
        session_start();

        // 退避したセッションデータを新しいセッションに復元
        $_SESSION = $session_data;

        // 新しいセッションには不要な情報を削除
        unset($_SESSION['destroyed']);
        unset($_SESSION['new_session_id']);
    }

    /**
     * セッション開始時に古いセッションの確認を行う
     *
     * SessionMiddlewareから呼び出される
     *
     * @return void
     * @throws \Exception セッションハイジャックの可能性がある場合
     */
    public static function validateSessionOnStart(): void
    {
        // 破棄されたセッションの確認
        if (isset($_SESSION['destroyed'])) {
            $destroyed_time = $_SESSION['destroyed'];

            // 300秒（5分）以上前に破棄されたセッションの場合
            if ($destroyed_time < time() - 300) {
                // セッションハイジャックまたはネットワーク問題の可能性
                // セッションを完全にクリア
                session_unset();
                session_destroy();
                session_start();

                // ログに記録（オプション）
                // error_log("古いセッションIDでのアクセス検出: destroyed_time={$destroyed_time}");

                return;
            }

            // 新しいセッションIDが保存されている場合（Cookie喪失による再試行）
            if (isset($_SESSION['new_session_id'])) {
                // 新しいセッションIDに切り替え
                $new_session_id = $_SESSION['new_session_id'];

                session_commit();
                session_id($new_session_id);
                session_start();

                // 切り替え成功後は不要な情報を削除
                unset($_SESSION['destroyed']);
                unset($_SESSION['new_session_id']);
            }
        }
    }
}
