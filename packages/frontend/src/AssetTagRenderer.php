<?php

declare(strict_types=1);

namespace CnA\Frontend;

use CnA\View\Renderer\HtmlEscaper;

class AssetTagRenderer
{
    /**
     * Render a stylesheet link tag.
     */
    public function renderStylesheetTag(string $url): string
    {
        $escaped = HtmlEscaper::escape($url);
        return sprintf('<link rel="stylesheet" href="%s">', $escaped);
    }

    /**
     * Render a modulepreload link tag.
     */
    public function renderPreloadTag(string $url): string
    {
        $escaped = HtmlEscaper::escape($url);
        return sprintf('<link rel="modulepreload" href="%s">', $escaped);
    }

    /**
     * Render an ECMAScript module script tag with optional CSP nonce.
     */
    public function renderModuleScriptTag(string $url, ?string $nonce = null): string
    {
        $escapedUrl = HtmlEscaper::escape($url);
        $nonceAttr = $nonce !== null ? sprintf(' nonce="%s"', HtmlEscaper::escape($nonce)) : '';
        return sprintf('<script type="module" src="%s"%s></script>', $escapedUrl, $nonceAttr);
    }

    /**
     * Render the CSRF token meta tag.
     */
    public function renderCsrfMetaTag(string $token): string
    {
        return sprintf('<meta name="csrf-token" content="%s">', HtmlEscaper::escape($token));
    }
}
