<?php
namespace App\Controllers;

use App\Models\Trigger;
use Psr\Container\ContainerInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

require __DIR__ . "/../models/user.php";

class HogeController {
    protected $container;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
    }

    public function index($request, $response, $args) {
        $users = Trigger::all();
        return $this->container['view']->render($response,
        'index.twig',
        ['users' => $users]);
    }
}