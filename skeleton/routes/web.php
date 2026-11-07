<?php

declare(strict_types=1);

use CnA\Contracts\Http\RequestInterface;
use CnA\Contracts\Routing\RouterInterface;

/** @var RouterInterface $router */

$router->get('/', function (RequestInterface $request) {
    if ($request->wantsJson()) {
        return json([
            'framework' => 'CnA Framework',
            'status' => 'operational',
            'version' => '1.0.0-dev',
        ]);
    }

    return view('welcome', [
        'title' => 'CnA Framework - Architecture, Features & Command Reference',
    ]);
})->name('home');
