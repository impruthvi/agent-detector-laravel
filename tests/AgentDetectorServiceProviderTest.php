<?php

use AgentDetector\Laravel\AgentContext;
use AgentDetector\Laravel\AgentDetectorServiceProvider;
use Illuminate\Support\Facades\Log;

it('binds AgentContext as singleton', function () {
    expect($this->app->bound(AgentContext::class))->toBeTrue();
});

it('returns same instance on repeated resolution', function () {
    $a = $this->app->make(AgentContext::class);
    $b = $this->app->make(AgentContext::class);
    expect($a)->toBe($b);
});

it('emits Log::warning when VerifyCsrfToken not container-bound and disable_csrf is true', function () {
    config(['agent-detector.disable_csrf' => true]);

    $warned = false;

    Log::shouldReceive('warning')
        ->once()
        ->with(\Mockery::pattern('/CSRF bypass unavailable/'))
        ->andReturnUsing(function () use (&$warned) { $warned = true; });

    Log::shouldReceive('extend')->andReturn(null);

    // Unbind VerifyCsrfToken so extend() path is skipped
    $app = $this->app;
    unset($app[\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

    $provider = new AgentDetectorServiceProvider($app);
    $provider->boot();

    expect($warned)->toBeTrue();
});
