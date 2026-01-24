<?php

declare(strict_types=1);

use App\Application\Actions\AddAction\AddMessageExecAction;
use App\Application\Actions\AddAction\AddSituationAction;
use App\Application\Actions\AddAction\AddSituationExecAction;
use App\Application\Actions\AddAction\AddTriggerAction;
use App\Application\Actions\AddAction\AddTriggerExecAction;
use App\Application\Actions\DeleteAction\DeleteMessageExecAction;
use App\Application\Actions\DeleteAction\DeleteSituationAction;
use App\Application\Actions\DeleteAction\DeleteTriggerAction;
use App\Application\Actions\EditAction\EditMessageAction;
use App\Application\Actions\EditAction\EditSituationAction;
use App\Application\Actions\EditAction\EditSituationNameAction;
use App\Application\Actions\EditAction\EditSituationNameExecAction;
use App\Application\Actions\EditAction\EditTriggerNameAction;
use App\Application\Actions\EditAction\EditTriggerNameExecAction;
use App\Application\Actions\EditAction\UpdateMessageExecAction;
use App\Application\Actions\EditAction\UpdateMessageOrderAction;
use App\Application\Actions\LoginAction\GuestLoginAction;
use App\Application\Actions\LoginAction\LineAuthCallbackAction;
use App\Application\Actions\LoginAction\LineLoginAction;
use App\Application\Actions\LoginAction\LogoutAction;
use App\Application\Actions\ShowAction\ShowMessageAction;
use App\Application\Actions\ShowAction\ShowSituationAction;
use App\Application\Actions\ShowAction\ShowTriggerAction;
use App\Application\Actions\LoginAction\ShowLoginPageAction;
use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use App\Application\Middleware\AuthenticationMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;


return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    // ============================================
    // 認証不要なルート（ログイン関連）
    // ============================================
    $app->get('/', ShowLoginPageAction::class); // ログインページ
    $app->get('/line_login', LineLoginAction::class); // LINEログイン
    $app->get('/line_login/callback', LineAuthCallbackAction::class); // LINEログイン コールバック
    $app->get('/guest_login', GuestLoginAction::class); // ゲストログイン
    $app->get('/logout', LogoutAction::class); // ログアウト
    $app->get('/show_login_page', ShowLoginPageAction::class); // ログアウトボタンを押された時のAPI

    // ============================================
    // 認証が必要なルート（AuthenticationMiddleware適用）
    // ============================================
    $app->group('', function (Group $group) {
        // Trigger
        $group->get('/show_trigger', ShowTriggerAction::class);
        $group->get('/add_trigger', AddTriggerAction::class);
        $group->get('/edit_trigger_name', EditTriggerNameAction::class);
        $group->post('/add_trigger_exec', AddTriggerExecAction::class);
        $group->post('/delete_trigger', DeleteTriggerAction::class);
        $group->post('/edit_trigger_name_exec', EditTriggerNameExecAction::class);

        // Situation
        $group->post('/add_situation_exec', AddSituationExecAction::class);
        $group->get('/show_situation', ShowSituationAction::class);
        $group->get('/edit_situation_name', EditSituationNameAction::class);
        $group->post('/delete_situation', DeleteSituationAction::class);
        $group->post('/edit_situation_name_exec', EditSituationNameExecAction::class);

        // Message
        $group->get('/show_message', ShowMessageAction::class);
        $group->post('/add_message', AddMessageExecAction::class);
        $group->get('/edit_message', EditMessageAction::class);
        $group->post('/update_message', UpdateMessageExecAction::class);
        $group->post('/delete_message', DeleteMessageExecAction::class);
        $group->post('/update_message_order', UpdateMessageOrderAction::class);

        // Users
        $group->group('/users', function (Group $nestedGroup) {
            $nestedGroup->get('', ListUsersAction::class);
            $nestedGroup->get('/{id}', ViewUserAction::class);
        });
    })->add(AuthenticationMiddleware::class);
};
