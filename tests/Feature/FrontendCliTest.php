<?php

declare(strict_types=1);

namespace Tests\Feature;

use CnA\Console\Command;
use CnA\Console\Commands\FrontendBuildCommand;
use CnA\Console\Commands\FrontendClearCommand;
use CnA\Console\Commands\FrontendDevCommand;
use CnA\Console\Commands\FrontendStatusCommand;
use CnA\Console\Commands\MakeComponentCommand;
use CnA\Console\Commands\MakeUiModuleCommand;
use CnA\Testing\TestCase;

class FrontendCliTest extends TestCase
{
    public function testFrontendStatusCommandReturnsSuccess(): void
    {
        $app = $this->createApplication();
        $cmd = new FrontendStatusCommand($app);

        ob_start();
        $status = $cmd->run([], []);
        ob_end_clean();

        $this->assertEquals(Command::SUCCESS, $status);
    }

    public function testFrontendBuildCommandVerifiesExistingManifest(): void
    {
        $app = $this->createApplication();
        $cmd = new FrontendBuildCommand($app);

        ob_start();
        $status = $cmd->run([], []);
        ob_end_clean();

        $this->assertEquals(Command::SUCCESS, $status);
    }

    public function testFrontendClearCommandClearsCache(): void
    {
        $app = $this->createApplication();
        $cmd = new FrontendClearCommand($app);

        ob_start();
        $status = $cmd->run([], []);
        ob_end_clean();

        $this->assertEquals(Command::SUCCESS, $status);
    }

    public function testFrontendDevCommandRuns(): void
    {
        $app = $this->createApplication();
        $cmd = new FrontendDevCommand($app);

        ob_start();
        $status = $cmd->run([], []);
        ob_end_clean();

        $this->assertEquals(Command::SUCCESS, $status);
    }

    public function testMakeUiModuleScaffoldsDirectoryAndFiles(): void
    {
        $app = $this->createApplication();
        $cmd = new MakeUiModuleCommand($app);

        $moduleName = 'testmodule' . uniqid();
        $modulePath = $app->basePath('resources/modules/' . $moduleName);

        try {
            ob_start();
            $status = $cmd->run([$moduleName], []);
            ob_end_clean();

            $this->assertEquals(Command::SUCCESS, $status);
            $this->assertTrue(is_dir($modulePath));
            $this->assertTrue(file_exists($modulePath . '/index.ts'));
            $this->assertTrue(file_exists($modulePath . '/' . $moduleName . '.css'));
        } finally {
            if (is_dir($modulePath)) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($modulePath, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($files as $file) {
                    if ($file->isDir()) {
                        rmdir($file->getRealPath());
                    } else {
                        unlink($file->getRealPath());
                    }
                }
                rmdir($modulePath);
            }
        }
    }

    public function testMakeComponentScaffoldsPhpClass(): void
    {
        $app = $this->createApplication();
        $cmd = new MakeComponentCommand($app);

        $compName = 'TestWidget' . uniqid();
        $classFile = $app->basePath('app/View/Components/' . $compName . 'Component.php');

        try {
            ob_start();
            $status = $cmd->run([$compName], []);
            ob_end_clean();

            $this->assertEquals(Command::SUCCESS, $status);
            $this->assertTrue(file_exists($classFile));
            $content = file_get_contents($classFile);
            $this->assertTrue(str_contains((string) $content, 'class ' . $compName . 'Component'));
        } finally {
            if (file_exists($classFile)) {
                unlink($classFile);
            }
        }
    }
}
