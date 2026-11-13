<?php

declare(strict_types=1);

namespace CnA\Admin;

use CnA\Contracts\Http\RequestInterface;
use CnA\Contracts\Http\ResponseInterface;
use CnA\Http\JsonResponse;
use CnA\Http\Response;

class AdminController
{
    public function __construct(protected AdminStudio $studio)
    {
    }

    public function overview(RequestInterface $request): ResponseInterface
    {
        return new JsonResponse($this->studio->getDashboardSummary());
    }

    public function dashboard(RequestInterface $request): ResponseInterface
    {
        if ($request->query('format') === 'json') {
            return $this->overview($request);
        }

        $data = $this->studio->getDashboardSummary();
        $html = $this->renderHtml($data);

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    protected function renderHtml(array $data): string
    {
        $version = htmlspecialchars((string) $data['version'], ENT_QUOTES, 'UTF-8');
        $phpVersion = htmlspecialchars((string) $data['php_version'], ENT_QUOTES, 'UTF-8');
        $env = htmlspecialchars((string) $data['environment'], ENT_QUOTES, 'UTF-8');
        $memMb = htmlspecialchars((string) $data['memory_usage_mb'], ENT_QUOTES, 'UTF-8');
        $peakMb = htmlspecialchars((string) $data['memory_peak_mb'], ENT_QUOTES, 'UTF-8');
        $dbStatus = htmlspecialchars((string) $data['database_status'], ENT_QUOTES, 'UTF-8');
        $secStatus = htmlspecialchars((string) $data['security_status'], ENT_QUOTES, 'UTF-8');

        $secColor = $secStatus === 'PASS' ? '#34d399' : ($secStatus === 'WARN' ? '#fbbf24' : '#f87171');
        $dbColor = $dbStatus === 'healthy' ? '#34d399' : '#fbbf24';

        $pluginsCount = (int) $data['plugins_count'];
        $tenantsCount = (int) $data['tenants_count'];
        $workflowsCount = (int) $data['workflows_count'];
        $featuresCount = (int) $data['features_count'];

        $workflowsList = '';
        if (empty($data['workflows'])) {
            $workflowsList = '<div class="empty-state">No workflows registered yet. Register workflows using <code>$app->make(\'workflow\')->register(...)</code>.</div>';
        } else {
            $workflowsList = '<ul class="item-list">';
            foreach ($data['workflows'] as $wf) {
                $workflowsList .= '<li><span class="dot"></span>' . htmlspecialchars((string) $wf, ENT_QUOTES, 'UTF-8') . '</li>';
            }
            $workflowsList .= '</ul>';
        }

        $tenantsList = '';
        if (empty($data['tenants'])) {
            $tenantsList = '<div class="empty-state">No tenants registered. Tenancy supports headers (<code>X-Tenant-ID</code>) or subdomains.</div>';
        } else {
            $tenantsList = '<ul class="item-list">';
            foreach ($data['tenants'] as $t) {
                $tenantsList .= '<li><span class="dot"></span>' . htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8') . '</li>';
            }
            $tenantsList .= '</ul>';
        }

        $featuresList = '';
        if (empty($data['features'])) {
            $featuresList = '<div class="empty-state">No feature flags defined in <code>config/features.php</code>.</div>';
        } else {
            $featuresList = '<ul class="item-list">';
            foreach ($data['features'] as $f) {
                $featuresList .= '<li><span class="dot"></span>' . htmlspecialchars((string) $f, ENT_QUOTES, 'UTF-8') . '</li>';
            }
            $featuresList .= '</ul>';
        }

        $pluginsList = '';
        if (empty($data['plugins'])) {
            $pluginsList = '<div class="empty-state">No plugins loaded. Scaffold new plugins using <code>php cna make:plugin &lt;name&gt;</code>.</div>';
        } else {
            $pluginsList = '<ul class="item-list">';
            foreach ($data['plugins'] as $p) {
                $pluginsList .= '<li><span class="dot"></span>' . htmlspecialchars((string) $p, ENT_QUOTES, 'UTF-8') . '</li>';
            }
            $pluginsList .= '</ul>';
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CnA Admin Studio - Complete Ecosystem Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #090d16;
            --surface: #0f172a;
            --surface-elevated: #1e293b;
            --border: rgba(148, 163, 184, 0.12);
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --primary: #38bdf8;
            --primary-glow: rgba(56, 189, 248, 0.2);
            --accent: #818cf8;
            --success: #34d399;
            --warning: #fbbf24;
            --code-bg: #060911;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.6;
            padding-bottom: 4rem;
        }
        nav {
            backdrop-filter: blur(16px);
            background: rgba(9, 13, 22, 0.9);
            border-bottom: 1px solid var(--border);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 800;
            font-size: 1.15rem;
            color: #fff;
            text-decoration: none;
        }
        .brand-badge {
            background: var(--surface-elevated);
            border: 1px solid var(--border);
            color: var(--primary);
            font-size: 0.75rem;
            font-family: 'JetBrains Mono', monospace;
            padding: 0.2rem 0.6rem;
            border-radius: 9999px;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .nav-link {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.15s;
        }
        .nav-link:hover { color: var(--primary); }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }
        .hero {
            margin-bottom: 2.5rem;
        }
        .hero h1 {
            font-size: 2.25rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #fff 40%, var(--primary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero p {
            color: var(--text-muted);
            font-size: 1.05rem;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2.5rem;
        }
        .metric-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, border-color 0.2s;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            border-color: rgba(56, 189, 248, 0.3);
        }
        .metric-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }
        .metric-val {
            font-size: 1.6rem;
            font-weight: 800;
            color: #fff;
            font-family: 'JetBrains Mono', monospace;
        }
        .metric-sub {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }
        .status-pill {
            display: inline-block;
            font-size: 0.75rem;
            padding: 0.2rem 0.6rem;
            border-radius: 6px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .subsystem-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
        }
        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
        }
        .panel-title {
            font-size: 1.1rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .panel-count {
            background: var(--surface-elevated);
            color: var(--primary);
            font-size: 0.8rem;
            font-weight: 700;
            padding: 0.15rem 0.5rem;
            border-radius: 9999px;
            font-family: 'JetBrains Mono', monospace;
        }
        .item-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .item-list li {
            background: var(--surface-elevated);
            padding: 0.6rem 0.8rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-family: 'JetBrains Mono', monospace;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .dot {
            width: 8px;
            height: 8px;
            background: var(--primary);
            border-radius: 50%;
            display: inline-block;
        }
        .empty-state {
            color: var(--text-muted);
            font-size: 0.85rem;
            font-style: italic;
            padding: 0.5rem 0;
        }
        code {
            background: var(--code-bg);
            border: 1px solid var(--border);
            padding: 0.1rem 0.4rem;
            border-radius: 4px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.8em;
            color: var(--primary);
        }
        .api-banner {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .btn {
            background: var(--primary);
            color: #090d16;
            text-decoration: none;
            font-weight: 700;
            font-size: 0.85rem;
            padding: 0.6rem 1.25rem;
            border-radius: 8px;
            transition: opacity 0.2s;
        }
        .btn:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <nav>
        <a href="/" class="brand">
            <span>CnA Framework</span>
            <span class="brand-badge">Admin Studio</span>
        </a>
        <div class="nav-links">
            <a href="/" class="nav-link">Welcome App</a>
            <a href="/admin/api/overview" target="_blank" class="nav-link">JSON Telemetry</a>
            <a href="https://alan2k2.github.io/CnA-Framework/" target="_blank" class="nav-link">Docs</a>
        </div>
    </nav>

    <div class="container">
        <div class="hero">
            <h1>Admin Studio & Observability</h1>
            <p>Real-time telemetry, modular ecosystem status, and runtime diagnostics for CnA Framework v{$version}.</p>
        </div>

        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-label">Runtime Engine</div>
                <div class="metric-val">v{$version}</div>
                <div class="metric-sub">PHP {$phpVersion} ({$env})</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Memory Usage</div>
                <div class="metric-val">{$memMb} MB</div>
                <div class="metric-sub">Peak: {$peakMb} MB</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Database Subsystem</div>
                <div class="metric-val" style="color: {$dbColor}; text-transform: capitalize;">{$dbStatus}</div>
                <div class="metric-sub">Default SQLite / PDO</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Security Audit</div>
                <div class="metric-val" style="color: {$secColor};">{$secStatus}</div>
                <div class="metric-sub">ASVS Level 2 Baseline</div>
            </div>
        </div>

        <div class="subsystem-grid">
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">🔄 Workflows & State Machines</div>
                    <div class="panel-count">{$workflowsCount}</div>
                </div>
                {$workflowsList}
            </div>

            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">🏢 Multi-Tenancy Subsystem</div>
                    <div class="panel-count">{$tenantsCount}</div>
                </div>
                {$tenantsList}
            </div>

            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">🚩 Feature Flags Engine</div>
                    <div class="panel-count">{$featuresCount}</div>
                </div>
                {$featuresList}
            </div>

            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">🧩 Plugins & Extensions</div>
                    <div class="panel-count">{$pluginsCount}</div>
                </div>
                {$pluginsList}
            </div>
        </div>

        <div class="api-banner">
            <div>
                <h3 style="margin-bottom: 0.25rem;">Live Telemetry API Available</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem;">You can query this real-time overview programmatically via HTTP GET or CLI.</p>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <a href="/admin/api/overview" target="_blank" class="btn">View Raw JSON</a>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }
}

