<?php

declare(strict_types=1);

namespace CnA\Contracts\Http;

interface MiddlewareInterface
{
    public function process(RequestInterface $request, callable $next): ResponseInterface;
}

