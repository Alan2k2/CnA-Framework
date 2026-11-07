<?php

declare(strict_types=1);

namespace CnA\Config;

class Config
{
    /** @var array<string, mixed> */
    protected array $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public static function loadFromDirectory(string $path): static
    {
        $config = new static();
        if (!is_dir($path)) {
            return $config;
        }

        $files = glob(rtrim($path, '/\\') . '/*.php');
        if ($files === false) {
            return $config;
        }

        foreach ($files as $file) {
            $key = basename($file, '.php');
            $data = require $file;
            if (is_array($data)) {
                $config->set($key, $data);
            }
        }

        return $config;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $array = $this->items;

        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (!str_contains($key, '.')) {
            return $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $array = &$this->items;

        while (count($keys) > 1) {
            $k = array_shift($keys);
            if (!isset($array[$k]) || !is_array($array[$k])) {
                $array[$k] = [];
            }
            $array = &$array[$k];
        }

        $array[array_shift($keys)] = $value;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function string(string $key, string $default = ''): string
    {
        $val = $this->get($key, $default);
        return is_string($val) ? $val : (string) $val;
    }

    public function int(string $key, int $default = 0): int
    {
        $val = $this->get($key, $default);
        return is_int($val) ? $val : (int) $val;
    }

    public function float(string $key, float $default = 0.0): float
    {
        $val = $this->get($key, $default);
        return is_float($val) ? $val : (float) $val;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $val = $this->get($key, $default);
        return is_bool($val) ? $val : (bool) $val;
    }

    public function array(string $key, array $default = []): array
    {
        $val = $this->get($key, $default);
        return is_array($val) ? $val : $default;
    }

    public function all(): array
    {
        return $this->items;
    }
}

