<?php

declare(strict_types=1);

namespace CnA\Http;

class JsonResponse extends Response
{
    public function __construct(mixed $data = [], int $status = 200, array $headers = [], int $encodingOptions = 0)
    {
        $options = $encodingOptions ?: (JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $body = json_encode($data, $options);

        $headers['Content-Type'] = 'application/json';
        parent::__construct($body, $status, $headers);
    }

    public static function from(mixed $data = [], int $status = 200): static
    {
        return new static($data, $status);
    }
}

