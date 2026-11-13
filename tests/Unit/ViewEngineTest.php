<?php

declare(strict_types=1);

namespace Tests\Unit;

use CnA\Testing\TestCase;
use CnA\View\Components\AlertComponent;
use CnA\View\Components\BadgeComponent;
use CnA\View\Components\ButtonComponent;
use CnA\View\Components\CardComponent;
use CnA\View\Components\ComponentRegistry;
use CnA\View\Components\InputComponent;
use CnA\View\Components\ModalComponent;
use CnA\View\Components\TableComponent;
use CnA\View\Engine\ViewEngine;
use CnA\View\Engine\ViewNotFoundException;
use CnA\View\Renderer\ComponentRenderer;
use CnA\View\Renderer\HtmlEscaper;

class ViewEngineTest extends TestCase
{
    protected string $tempViewDir;

    protected function setUp(): void
    {
        $this->tempViewDir = sys_get_temp_dir() . '/cna_test_views_' . uniqid();
        mkdir($this->tempViewDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempViewDir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->tempViewDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
            rmdir($this->tempViewDir);
        }
    }

    public function testRendersSimpleTemplate(): void
    {
        file_put_contents($this->tempViewDir . '/hello.php', 'Hello, <?= $name ?>!');
        $engine = new ViewEngine(null, [$this->tempViewDir]);

        $output = $engine->render('hello', ['name' => 'Alan']);
        $this->assertEquals('Hello, Alan!', $output);
    }

    public function testThrowsExceptionOnMissingView(): void
    {
        $engine = new ViewEngine(null, [$this->tempViewDir]);
        $threw = false;

        try {
            $engine->render('nonexistent');
        } catch (ViewNotFoundException $e) {
            $threw = true;
            $this->assertTrue(str_contains($e->getMessage(), 'nonexistent'));
        }

        $this->assertTrue($threw, 'ViewNotFoundException must be thrown for missing views');
    }

    public function testSharedDataAcrossViews(): void
    {
        file_put_contents($this->tempViewDir . '/shared_test.php', 'App: <?= $appName ?>');
        $engine = new ViewEngine(null, [$this->tempViewDir]);
        $engine->share('appName', 'CnA Framework v2.0');

        $output = $engine->render('shared_test');
        $this->assertEquals('App: CnA Framework v2.0', $output);
    }

    public function testLayoutAndSectionWrapping(): void
    {
        mkdir($this->tempViewDir . '/layouts', 0755, true);
        file_put_contents($this->tempViewDir . '/layouts/app.php', '<main><?= $this->yieldSection("content") ?></main>');
        file_put_contents($this->tempViewDir . '/page.php', '<?php $this->layout("layouts.app"); ?><h1>Title</h1>');

        $engine = new ViewEngine(null, [$this->tempViewDir]);
        $output = $engine->render('page');

        $this->assertEquals('<main><h1>Title</h1></main>', $output);
    }

    public function testHtmlEscaperProtectsAgainstXss(): void
    {
        $malicious = '<script>alert("XSS")</script>';
        $escaped = HtmlEscaper::escape($malicious);
        $this->assertEquals('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $escaped);
    }

    public function testButtonComponentRendering(): void
    {
        $btn = new ButtonComponent([
            'label' => 'Submit Order',
            'variant' => 'primary',
            'size' => 'large',
        ]);
        $html = $btn->render();

        $this->assertTrue(str_contains($html, '<button'));
        $this->assertTrue(str_contains($html, 'cna-btn-primary'));
        $this->assertTrue(str_contains($html, 'cna-btn-large'));
        $this->assertTrue(str_contains($html, 'Submit Order'));
    }

    public function testButtonComponentAsAnchorLink(): void
    {
        $btn = new ButtonComponent([
            'label' => 'Learn More',
            'href' => 'https://cna.dev/docs',
            'variant' => 'outline',
        ]);
        $html = $btn->render();

        $this->assertTrue(str_contains($html, '<a href="https://cna.dev/docs"'));
        $this->assertTrue(str_contains($html, 'role="button"'));
        $this->assertTrue(str_contains($html, 'cna-btn-outline'));
        $this->assertTrue(str_contains($html, 'Learn More'));
    }

    public function testAlertComponentRendering(): void
    {
        $alert = new AlertComponent([
            'title' => 'Security Notice',
            'message' => 'Your session is encrypted using AES-256-GCM.',
            'variant' => 'success',
            'dismissible' => true,
        ]);
        $html = $alert->render();

        $this->assertTrue(str_contains($html, 'cna-alert-success'));
        $this->assertTrue(str_contains($html, 'Security Notice'));
        $this->assertTrue(str_contains($html, 'Your session is encrypted'));
        $this->assertTrue(str_contains($html, 'cna-alert-close'));
    }

    public function testBadgeComponentRendering(): void
    {
        $badge = new BadgeComponent([
            'label' => 'v2.0 Stable',
            'variant' => 'primary',
            'pill' => true,
        ]);
        $html = $badge->render();

        $this->assertTrue(str_contains($html, 'cna-badge-primary'));
        $this->assertTrue(str_contains($html, 'rounded-full'));
        $this->assertTrue(str_contains($html, 'v2.0 Stable'));
    }

    public function testCardComponentRendering(): void
    {
        $card = new CardComponent([
            'title' => 'Vite Pipeline',
            'subtitle' => 'Zero config HMR',
            'content' => '<p>Asset engine online.</p>',
            'footer' => '<span>Status: Active</span>',
        ]);
        $html = $card->render();

        $this->assertTrue(str_contains($html, 'Vite Pipeline'));
        $this->assertTrue(str_contains($html, 'Zero config HMR'));
        $this->assertTrue(str_contains($html, '<p>Asset engine online.</p>'));
        $this->assertTrue(str_contains($html, 'Status: Active'));
    }

    public function testInputComponentRenderingWithError(): void
    {
        $input = new InputComponent([
            'name' => 'email',
            'label' => 'User Email',
            'placeholder' => 'user@example.com',
            'required' => true,
            'error' => 'Invalid email address provided',
        ]);
        $html = $input->render();

        $this->assertTrue(str_contains($html, 'User Email'));
        $this->assertTrue(str_contains($html, 'name="email"'));
        $this->assertTrue(str_contains($html, 'required'));
        $this->assertTrue(str_contains($html, 'aria-invalid="true"'));
        $this->assertTrue(str_contains($html, 'Invalid email address provided'));
    }

    public function testModalComponentRendering(): void
    {
        $modal = new ModalComponent([
            'id' => 'order_modal',
            'title' => 'Confirm Order',
            'content' => 'Are you sure you want to process this order?',
        ]);
        $html = $modal->render();

        $this->assertTrue(str_contains($html, 'id="order_modal"'));
        $this->assertTrue(str_contains($html, 'role="dialog"'));
        $this->assertTrue(str_contains($html, 'Confirm Order'));
        $this->assertTrue(str_contains($html, 'Are you sure you want to process this order?'));
    }

    public function testTableComponentRendering(): void
    {
        $table = new TableComponent([
            'headers' => ['ID', 'Customer', 'Status'],
            'rows' => [
                [1, 'Alan Shaju', 'Shipped'],
                [2, 'Jane Doe', 'Pending'],
            ],
            'striped' => true,
        ]);
        $html = $table->render();

        $this->assertTrue(str_contains($html, '<th scope="col"'));
        $this->assertTrue(str_contains($html, 'Alan Shaju'));
        $this->assertTrue(str_contains($html, 'Jane Doe'));
        $this->assertTrue(str_contains($html, 'Shipped'));
    }

    public function testComponentRegistryAndRendererResolution(): void
    {
        $registry = new ComponentRegistry();
        $this->assertTrue($registry->has('ui.button'));
        $this->assertTrue($registry->has('ui.alert'));
        $this->assertTrue($registry->has('ui.card'));

        $renderer = new ComponentRenderer($registry);
        $rendered = $renderer->render('ui.badge', ['label' => 'Certified', 'variant' => 'success']);
        $this->assertTrue(str_contains($rendered, 'Certified'));
    }
}
