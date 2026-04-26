<?php

use AgentDetector\Laravel\DetectedAgentMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

it('adds X-Agent-Session header when agent detected', function () {
    putenv('CLAUDECODE=1');

    $middleware = $this->app->make(DetectedAgentMiddleware::class);
    $request    = Request::create('/test', 'GET');

    $response = $middleware->handle($request, fn () => new Response('ok'));

    expect($response->headers->has('X-Agent-Session'))->toBeTrue();
    expect($response->headers->get('X-Agent-Session'))->toContain('Claude Code');
});

it('does not add X-Agent-Session header when no agent', function () {
    $middleware = $this->app->make(DetectedAgentMiddleware::class);
    $request    = Request::create('/test', 'GET');

    $response = $middleware->handle($request, fn () => new Response('ok'));

    expect($response->headers->has('X-Agent-Session'))->toBeFalse();
});

it('resolves via constructor injection from container', function () {
    expect(fn () => $this->app->make(DetectedAgentMiddleware::class))->not->toThrow(\Throwable::class);
});
