<?php

declare(strict_types=1);

namespace Tests\Feature;

use CnA\Console\Command;
use CnA\Console\Commands\AppInfoCommand;
use CnA\Console\Commands\ConfigShowCommand;
use CnA\Console\Commands\EnvCheckCommand;
use CnA\Console\Commands\HealthCheckCommand;
use CnA\Console\Commands\MakeModuleCommand;
use CnA\Console\Commands\MakePluginCommand;
use CnA\Console\Commands\SecurityAuditCommand;
use CnA\Console\Commands\SecurityDependenciesCommand;
use CnA\Console\Commands\SecurityHeadersCommand;
use CnA\Console\Commands\SecurityPermissionsCommand;
use CnA\Console\Commands\SecurityReportCommand;
use CnA\Console\Commands\WorkflowListCommand;
use CnA\Console\Commands\WorkflowRunCommand;
use CnA\Testing\TestCase;

class EcosystemCliTest extends TestCase
{
    public function testAppInfoCommandReturnsSuccess(): void
    {
        $app = $this->createApplication();
        $cmd = new AppInfoCommand($app);

        ob_start();
        $status = $cmd->run([], []);
        $out = ob_get_clean();

        $this->assertEquals(Command::SUCCESS, $status);
        $this->assertTrue(str_contains($out, 'CnA Modular Application Platform'));
    }

    public function testHealthCheckCommandReturnsSuccess(): void
    {
        $app = $this->createApplication();
        $cmd = new HealthCheckCommand($app);

        ob_start();
        $status = $cmd->run([], []);
        $out = ob_get_clean();

        $this->assertEquals(Command::SUCCESS, $status);
        $this->assertTrue(str_contains($out, 'PHP Runtime'));
    }

    public function testEnvCheckCommandReturnsSuccess(): void
    {
        $app = $this->createApplication();
        $cmd = new EnvCheckCommand($app);

        ob_start();
        $status = $cmd->run([], []);
        ob_end_clean();

        $this->assertEquals(Command::SUCCESS, $status);
    }

    public function testSecurityAuditAndSubcommands(): void
    {
        $app = $this->createApplication();

        // 1. Audit
        $auditCmd = new SecurityAuditCommand($app);
        ob_start();
        $status = $auditCmd->run([], []);
        ob_end_clean();
        $this->assertEquals(Command::SUCCESS, $status);

        // 2. Dependencies
        $depsCmd = new SecurityDependenciesCommand($app);
        ob_start();
        $status = $depsCmd->run([], []);
        ob_end_clean();
        $this->assertEquals(Command::SUCCESS, $status);

        // 3. Headers
        $headersCmd = new SecurityHeadersCommand($app);
        ob_start();
        $status = $headersCmd->run([], []);
        ob_end_clean();
        $this->assertEquals(Command::SUCCESS, $status);

        // 4. Permissions
        $permsCmd = new SecurityPermissionsCommand($app);
        ob_start();
        $status = $permsCmd->run([], []);
        ob_end_clean();
        $this->assertEquals(Command::SUCCESS, $status);

        // 5. JSON Report
        $reportCmd = new SecurityReportCommand($app);
        ob_start();
        $status = $reportCmd->run([], ['format' => 'json']);
        $jsonOut = ob_get_clean();
        $this->assertEquals(Command::SUCCESS, $status);
        $this->assertTrue(str_contains($jsonOut, '"status":'));
    }

    public function testConfigShowCommand(): void
    {
        $app = $this->createApplication();
        $cmd = new ConfigShowCommand($app);

        ob_start();
        $status = $cmd->run(['app'], []);
        $out = ob_get_clean();

        $this->assertEquals(Command::SUCCESS, $status);
        $this->assertTrue(str_contains($out, 'CnA Framework'));
        $this->assertTrue(str_contains($out, 'key: ********'));
    }

    public function testWorkflowListCommand(): void
    {
        $app = $this->createApplication();
        $cmd = new WorkflowListCommand($app);

        ob_start();
        $status = $cmd->run([], []);
        ob_end_clean();

        $this->assertEquals(Command::SUCCESS, $status);
    }
}
