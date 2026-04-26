<?php

namespace AgentDetector\Laravel\Tests;

use AgentDetector\Laravel\AgentDetectorServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [AgentDetectorServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        putenv('CLAUDECODE');
        putenv('CLAUDE_CODE');
        putenv('CODEX_SANDBOX');
        putenv('CODEX_THREAD_ID');
        putenv('AMP_CURRENT_THREAD_ID');
        putenv('CURSOR_AGENT');
        putenv('CLAUDE_CODE_SESSION_ID');
    }
}
