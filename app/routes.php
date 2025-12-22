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
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;


return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });
    // $app->get('/', function (Request $request, Response $response) {
    //     $response->getBody()->write('<a>/show_trigger</a>Hello worldおお!');
    //     return $response;
    // });

    // ログイン
    $app->get('/', ShowLoginPageAction::class); // ログインページ
    $app->get('/line_login', LineLoginAction::class); // LINEログイン
    $app->get('/line_login/callback', LineAuthCallbackAction::class); // LINEログイン コールバック
    $app->get('/guest_login', GuestLoginAction::class); // ゲストログイン
    $app->get('/logout', LogoutAction::class); // ログアウト
    // TODO: 以下はログアウトボタンを押された時のAPI
    $app->get('/show_login_page', ShowLoginPageAction::class);
    // Trigger
    $app->get('/show_trigger', ShowTriggerAction::class);
    $app->get('/add_trigger', AddTriggerAction::class);
    $app->get('/edit_trigger_name', EditTriggerNameAction::class);
    $app->post('/add_trigger_exec', AddTriggerExecAction::class);
    $app->post('/delete_trigger', DeleteTriggerAction::class);
    $app->post('/edit_trigger_name_exec', EditTriggerNameExecAction::class);
    // Situation
    $app->post('/add_situation_exec', AddSituationExecAction::class);
    $app->get('/add_situation', AddSituationAction::class);
    $app->get('/show_situation', ShowSituationAction::class);
    $app->get('/edit_situation_name', EditSituationNameAction::class);
    $app->post('/delete_situation', DeleteSituationAction::class);
    $app->post('/edit_situation_name_exec', EditSituationNameExecAction::class);
    // Message
    $app->get('/show_message', ShowMessageAction::class);
    $app->post('/add_message', AddMessageExecAction::class);
    $app->get('/edit_message', EditMessageAction::class);
    $app->post('/update_message', UpdateMessageExecAction::class);
    $app->post('/delete_message', DeleteMessageExecAction::class);
    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });
};
