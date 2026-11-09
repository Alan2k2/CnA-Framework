<?php

declare(strict_types=1);

namespace CnA\Storage\Disks;

use CnA\Contracts\Exceptions\SecurityException;
use CnA\Contracts\Storage\StorageInterface;

class LocalDisk implements StorageInterface
{
    protected string $root;

    public function __construct(string $root, protected string $urlPrefix = '/storage')
    {
        $this->root = rtrim(str_replace('\\', '/', $root), '/');
        if (!is_dir($this->root)) {
            mkdir($this->root, 0755, true);
        }
    }

    public function get(string $path): ?string
    {
        $fullPath = $this->applyPathPrefix($path);
        if (!file_exists($fullPath)) {
            return null;
        }
        $contents = file_get_contents($fullPath);
        return $contents === false ? null : $contents;
    }

    public function put(string $path, string $contents, array $options = []): bool
    {
        $fullPath = $this->applyPathPrefix($path);
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return file_put_contents($fullPath, $contents) !== false;
    }

    public function exists(string $path): bool
    {
        return file_exists($this->applyPathPrefix($path));
    }

    public function delete(string|array $paths): bool
    {
        $allSuccess = true;
        foreach ((array) $paths as $path) {
            $fullPath = $this->applyPathPrefix($path);
            if (file_exists($fullPath)) {
                if (!@unlink($fullPath)) {
                    $allSuccess = false;
                }
            }
        }
        return $allSuccess;
    }

    public function size(string $path): int
    {
        $fullPath = $this->applyPathPrefix($path);
        return file_exists($fullPath) ? (int) filesize($fullPath) : 0;
    }

    public function lastModified(string $path): int
    {
        $fullPath = $this->applyPathPrefix($path);
        return file_exists($fullPath) ? (int) filemtime($fullPath) : 0;
    }

    public function mimeType(string $path): ?string
    {
        $fullPath = $this->applyPathPrefix($path);
        if (!file_exists($fullPath)) {
            return null;
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $fullPath);
            finfo_close($finfo);
            return $mime !== false ? $mime : null;
        }

        return null;
    }

    public function url(string $path): string
    {
        return rtrim($this->urlPrefix, '/') . '/' . ltrim(str_replace('\\', '/', $path), '/');
    }

    public function temporaryUrl(string $path, \DateTimeInterface $expiration, array $options = []): string
    {
        $expires = $expiration->getTimestamp();
        $signature = hash_hmac('sha256', $path . $expires, 'app-storage-key');
        return $this->url($path) . '?expires=' . $expires . '&signature=' . $signature;
    }

    protected function applyPathPrefix(string $path): string
    {
        if (str_contains($path, "\0")) {
            throw new SecurityException("Null byte detected in storage path.");
        }

        $normalized = str_replace('\\', '/', $path);
        $clean = [];
        foreach (explode('/', $normalized) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                throw new SecurityException("Directory traversal attempt detected in path [{$path}].");
            }
            $clean[] = $segment;
        }

        $cleanPath = implode('/', $clean);
        $target = $this->root . ($cleanPath !== '' ? '/' . $cleanPath : '');
        $target = str_replace('\\', '/', $target);

        // Prefix boundary check (must match root or root + '/')
        if ($target !== $this->root && !str_starts_with($target, $this->root . '/')) {
            throw new SecurityException("Directory traversal boundary violation on path: [{$path}]");
        }

        // Canonical realpath check if target exists
        $real = realpath($target);
        if ($real !== false) {
            $realNorm = str_replace('\\', '/', $real);
            $rootReal = str_replace('\\', '/', realpath($this->root) ?: $this->root);
            if ($realNorm !== $rootReal && !str_starts_with($realNorm, $rootReal . '/')) {
                throw new SecurityException("Symlink directory traversal detected on path: [{$path}]");
            }
        }

        return $target;
    }
}

