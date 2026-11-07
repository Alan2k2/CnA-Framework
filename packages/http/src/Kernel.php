<?php

declare(strict_types=1);

namespace CnA\Http;

use CnA\Contracts\Core\ApplicationInterface;
use CnA\Contracts\Exceptions\AuthenticationException;
use CnA\Contracts\Exceptions\AuthorizationException;
use CnA\Contracts\Exceptions\LazyLoadingViolationException;
use CnA\Contracts\Exceptions\NotFoundException;
use CnA\Contracts\Exceptions\QueryBudgetExceededException;
use CnA\Contracts\Exceptions\ValidationException;
use CnA\Contracts\Http\RequestInterface;
use CnA\Contracts\Http\ResponseInterface;
use CnA\Contracts\Routing\RouterInterface;
use CnA\Core\Pipeline;

class Kernel
{
    /** @var array<string> */
    protected array $middleware = [];

    public function __construct(
        protected ApplicationInterface $app,
        protected RouterInterface $router
    ) {
    }

    public function setMiddleware(array $middleware): static
    {
        $this->middleware = $middleware;
        return $this;
    }

    public function handle(RequestInterface $request): ResponseInterface
    {
        $this->app->bootstrap();

        try {
            return (new Pipeline($this->app))
                ->send($request)
                ->through($this->middleware)
                ->then(fn($req) => $this->router->dispatch($req));
        } catch (\Throwable $e) {
            return $this->handleException($e, $request);
        }
    }

    public function terminate(RequestInterface $request, ResponseInterface $response): void
    {
        $this->app->terminate();
    }

    protected function handleException(\Throwable $e, RequestInterface $request): ResponseInterface
    {
        if ($e instanceof ValidationException) {
            return new JsonResponse([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        }

        if ($e instanceof AuthenticationException) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 401);
        }

        if ($e instanceof AuthorizationException) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 403);
        }

        if ($e instanceof NotFoundException) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 404);
        }

        if ($e instanceof LazyLoadingViolationException || $e instanceof QueryBudgetExceededException) {
            return new JsonResponse([
                'error' => 'Database Guardrail Violation',
                'message' => $e->getMessage(),
            ], 500);
        }

        $isProduction = $this->app->isProduction();

        $payload = [
            'message' => $isProduction ? 'Server Error' : $e->getMessage(),
            'correlation_id' => $request->correlationId(),
        ];

        if (!$isProduction) {
            $payload['exception'] = get_class($e);
            $payload['file'] = $e->getFile();
            $payload['line'] = $e->getLine();
            $payload['trace'] = explode("\n", $e->getTraceAsString());
        }

        return new JsonResponse($payload, 500);
    }
}

