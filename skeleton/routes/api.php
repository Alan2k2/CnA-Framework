<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\OrderController;
use CnA\Contracts\Routing\RouterInterface;

/** @var RouterInterface $router */

$router->group(['prefix' => '/api/v1'], function (RouterInterface $router) {
    $router->get('/health', [HealthController::class, 'check'])->name('health.check');
    $router->post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

    $router->get('/orders', [OrderController::class, 'index'])->name('orders.index');
    $router->post('/orders', [OrderController::class, 'store'])->name('orders.store');
    $router->get('/orders/{id}', [OrderController::class, 'show'])->name('orders.show');
});

