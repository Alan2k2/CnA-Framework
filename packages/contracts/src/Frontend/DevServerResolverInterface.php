<?php

declare(strict_types=1);

namespace CnA\Contracts\Frontend;

interface DevServerResolverInterface
{
    /**
     * Determine if the Vite development server is reachable on the local loopback.
     *
     * @return bool
     */
    public function isRunning(): bool;

    /**
     * Get the validated loopback dev server URL, optionally appending an asset path.
     *
     * @param string|null $path
     * @return string
     */
    public function getUrl(?string $path = null): string;

    /**
     * Verify whether a given URL is a trusted loopback dev-server origin.
     *
     * @param string $url
     * @return bool
     */
    public function isTrustedOrigin(string $url): bool;
}

