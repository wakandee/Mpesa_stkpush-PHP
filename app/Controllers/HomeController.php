<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\PaymentRepository;
use App\Support\Response;

final class HomeController
{
    public function redirectToApp(): void
    {
        Response::redirect('./app');
    }

    public function index(): void
    {
        $repository = new PaymentRepository();
        $payments = [];
        $databaseReady = true;
        $error = null;

        try {
            $payments = $repository->latest();
        } catch (\Throwable $exception) {
            $databaseReady = false;
            $error = $exception->getMessage();
        }

        require __DIR__ . '/../Views/home.php';
    }
}
