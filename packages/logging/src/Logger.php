<?php

declare(strict_types=1);

namespace CnA\Logging;

use CnA\Contracts\Logging\LoggerInterface;

class Logger implements LoggerInterface
{
    protected static ?string $correlationId = null;

    public function __construct(
        protected ?string $logPath = null,
        protected string $minimumLevel = 'debug'
    ) {
    }

    public static function setCorrelationId(?string $id): void
    {
        static::$correlationId = $id;
    }

    public static function getCorrelationId(): ?string
    {
        return static::$correlationId;
    }

    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function log(string $level, string|\Stringable $message, array $context = []): void
    {
        $context = $this->redactSensitive($context);

        $record = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => strtoupper($level),
            'message' => (string) $message,
            'correlation_id' => static::$correlationId,
            'context' => $context,
        ];

        $line = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;

        if ($this->logPath !== null) {
            $dir = dirname($this->logPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($this->logPath, $line, FILE_APPEND | LOCK_EX);
        }
    }

    protected function redactSensitive(array $data): array
    {
        $sensitiveKeys = ['password', 'secret', 'token', 'key', 'auth', 'card', 'cvv', 'api_key'];

        $redacted = [];
        foreach ($data as $k => $v) {
            $isSensitive = false;
            if (is_string($k)) {
                $lower = strtolower($k);
                foreach ($sensitiveKeys as $pattern) {
                    if (str_contains($lower, $pattern)) {
                        $isSensitive = true;
                        break;
                    }
                }
            }

            if ($isSensitive) {
                $redacted[$k] = '********';
            } elseif (is_array($v)) {
                $redacted[$k] = $this->redactSensitive($v);
            } else {
                $redacted[$k] = $v;
            }
        }

        return $redacted;
    }
}

