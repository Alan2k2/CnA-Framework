<?php

declare(strict_types=1);

namespace Tests\Unit;

use CnA\Frontend\AssetManager;
use CnA\Frontend\AssetResolver;
use CnA\Frontend\AssetTagRenderer;
use CnA\Frontend\DevServer;
use CnA\Frontend\Facades\Vite;
use CnA\Frontend\ManifestReader;
use CnA\Frontend\PreloadManager;
use CnA\FrontendReact\ReactAdapter;
use CnA\FrontendVue\VueAdapter;
use CnA\Testing\TestCase;
use InvalidArgumentException;
use RuntimeException;

class FrontendEngineTest extends TestCase
{
    protected string $tempDir;
    protected string $manifestFile;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/cna_frontend_test_' . uniqid();
        mkdir($this->tempDir . '/.vite', 0755, true);

        $manifestData = [
            'resources/js/app.ts' => [
                'file' => 'assets/app-1234.js',
                'src' => 'resources/js/app.ts',
                'isEntry' => true,
                'css' => ['assets/app-1234.css'],
                'imports' => ['_vendor-5678.js'],
            ],
            'resources/css/app.css' => [
                'file' => 'assets/app-1234.css',
                'src' => 'resources/css/app.css',
            ],
            '_vendor-5678.js' => [
                'file' => 'assets/vendor-5678.js',
                'css' => ['assets/vendor-theme.css'],
            ],
        ];

        $this->manifestFile = $this->tempDir . '/.vite/manifest.json';
        file_put_contents($this->manifestFile, json_encode($manifestData));
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
            rmdir($this->tempDir);
        }
    }

    public function testManifestReaderLoadsAndValidatesJson(): void
    {
        $reader = new ManifestReader($this->manifestFile);
        $manifest = $reader->load();

        $this->assertCount(3, $manifest);
        $this->assertTrue($reader->has('resources/js/app.ts'));

        $entry = $reader->get('resources/js/app.ts');
        $this->assertNotNull($entry);
        $this->assertEquals('assets/app-1234.js', $entry['file']);
    }

    public function testManifestReaderThrowsOnMissingFile(): void
    {
        $reader = new ManifestReader($this->tempDir . '/nonexistent.json');
        $threw = false;

        try {
            $reader->load();
        } catch (RuntimeException $e) {
            $threw = true;
            $this->assertTrue(str_contains($e->getMessage(), 'Vite manifest not found'));
        }

        $this->assertTrue($threw, 'ManifestReader must throw if manifest is missing');
    }

    public function testDevServerRejectsNonLoopbackOrigins(): void
    {
        $threw = false;
        try {
            new DevServer('http://external-malicious-domain.com:5173');
        } catch (InvalidArgumentException $e) {
            $threw = true;
            $this->assertTrue(str_contains($e->getMessage(), 'Dev server exposure blocked'));
        }

        $this->assertTrue($threw, 'DevServer must reject non-loopback hosts for ASVS security compliance');
    }

    public function testDevServerAllowsLoopback(): void
    {
        $server = new DevServer('http://127.0.0.1:5173');
        $this->assertEquals('http://127.0.0.1:5173', $server->getUrl());
        $this->assertEquals('http://127.0.0.1:5173/@vite/client', $server->getUrl('@vite/client'));
        $this->assertTrue($server->isTrustedOrigin('http://localhost:8000'));
    }

    public function testAssetResolverCollectsImportsAndCss(): void
    {
        $reader = new ManifestReader($this->manifestFile);
        $resolver = new AssetResolver($reader, $this->tempDir, '/build/', [
            'app' => 'resources/js/app.ts',
        ]);

        $resolved = $resolver->resolveEntry('app');

        $this->assertEquals('/build/assets/app-1234.js', $resolved['file']);
        $this->assertTrue(in_array('/build/assets/app-1234.css', $resolved['css'], true));
        $this->assertTrue(in_array('/build/assets/vendor-theme.css', $resolved['css'], true));
        $this->assertTrue(in_array('/build/assets/vendor-5678.js', $resolved['preloads'], true));
    }

    public function testAssetResolverBlocksPathTraversalAttacks(): void
    {
        $reader = new ManifestReader($this->manifestFile);
        $resolver = new AssetResolver($reader, $this->tempDir);

        $traversalAttempts = [
            '../../etc/passwd',
            '../app.ts',
            '/etc/shadow',
            'C:\\Windows\\system.ini',
        ];

        foreach ($traversalAttempts as $attempt) {
            $threw = false;
            try {
                $resolver->resolveEntry($attempt);
            } catch (InvalidArgumentException $e) {
                $threw = true;
                $this->assertTrue(str_contains($e->getMessage(), 'Path traversal attempt detected'));
            }
            $this->assertTrue($threw, "Path traversal attempt [{$attempt}] must be blocked");
        }
    }

    public function testPreloadManagerDeduplication(): void
    {
        $manager = new PreloadManager();
        $manager->addStylesheet('/build/app.css');
        $manager->addStylesheet('/build/app.css');
        $manager->addPreload('/build/vendor.js');
        $manager->addPreload('/build/vendor.js');

        $this->assertCount(1, $manager->getStylesheets());
        $this->assertCount(1, $manager->getPreloads());
    }

    public function testAssetTagRendererWithNonce(): void
    {
        $renderer = new AssetTagRenderer();
        $scriptTag = $renderer->renderModuleScriptTag('/build/app.js', 'secret-nonce-123');

        $this->assertTrue(str_contains($scriptTag, '<script type="module"'));
        $this->assertTrue(str_contains($scriptTag, 'src="/build/app.js"'));
        $this->assertTrue(str_contains($scriptTag, 'nonce="secret-nonce-123"'));

        $styleTag = $renderer->renderStylesheetTag('/build/app.css');
        $this->assertEquals('<link rel="stylesheet" href="/build/app.css">', $styleTag);
    }

    public function testAssetManagerDevModeGeneratesClientAndSourceTags(): void
    {
        $devServer = new DevServer('http://127.0.0.1:5173');
        $reader = new ManifestReader($this->manifestFile);
        $resolver = new AssetResolver($reader, $this->tempDir, '/build/', ['app' => 'resources/js/app.ts']);
        $preloads = new PreloadManager();
        $renderer = new AssetTagRenderer();

        $manager = new AssetManager($devServer, $resolver, $preloads, $renderer, ['app' => 'resources/js/app.ts'], true);

        $tags = $manager->assets('app', 'nonce-abc');

        $this->assertTrue(str_contains($tags, 'http://127.0.0.1:5173/@vite/client'));
        $this->assertTrue(str_contains($tags, 'http://127.0.0.1:5173/resources/js/app.ts'));
    }

    public function testAssetManagerProductionModeGeneratesHashedAssets(): void
    {
        $devServer = new DevServer('http://127.0.0.1:5173');
        $reader = new ManifestReader($this->manifestFile);
        $resolver = new AssetResolver($reader, $this->tempDir, '/build/', ['app' => 'resources/js/app.ts']);
        $preloads = new PreloadManager();
        $renderer = new AssetTagRenderer();

        $manager = new AssetManager($devServer, $resolver, $preloads, $renderer, ['app' => 'resources/js/app.ts'], false);

        $tags = $manager->assets('app');

        $this->assertTrue(str_contains($tags, '<link rel="stylesheet" href="/build/assets/app-1234.css">'));
        $this->assertTrue(str_contains($tags, '<link rel="modulepreload" href="/build/assets/vendor-5678.js">'));
        $this->assertTrue(str_contains($tags, '<script type="module" src="/build/assets/app-1234.js"></script>'));
    }

    public function testReactAdapterMountPointGeneration(): void
    {
        $mount = ReactAdapter::mount('OrderDashboard', ['orderId' => 999], 'react-order-root');

        $this->assertTrue(str_contains($mount, 'id="react-order-root"'));
        $this->assertTrue(str_contains($mount, 'class="cna-react-root'));
        $this->assertTrue(str_contains($mount, 'data-cna-react-component="OrderDashboard"'));
        $this->assertTrue(str_contains($mount, 'orderId'));
    }

    public function testVueAdapterMountPointGeneration(): void
    {
        $mount = VueAdapter::mount('UserAnalytics', ['range' => '7d'], 'vue-analytics-root');

        $this->assertTrue(str_contains($mount, 'id="vue-analytics-root"'));
        $this->assertTrue(str_contains($mount, 'class="cna-vue-root'));
        $this->assertTrue(str_contains($mount, 'data-cna-vue-component="UserAnalytics"'));
        $this->assertTrue(str_contains($mount, '7d'));
    }
}
