<?php

declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Controllers\HomeController;
use App\Controllers\SetupController;
use App\Controllers\MpesaExpressController;
use App\Support\Response;
use App\Support\Router;

$router = new Router();

$homeController = new HomeController();
$setupController = new SetupController();
$mpesaExpressController = new MpesaExpressController();

$router->get('/', [$homeController, 'redirectToApp']);
$router->get('/app', [$homeController, 'index']);
$router->get('/app/setup', [$setupController, 'index']);
$router->post('/app/mpesa-express/push', [$mpesaExpressController, 'push']);
$router->match(['GET', 'POST'], '/callback', [$mpesaExpressController, 'callback']);

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
} catch (Throwable $exception) {
    Response::html(
        'Application error: ' . htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8'),
        500
    );
}
