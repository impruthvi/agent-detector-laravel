<?php

use AgentDetector\AgentDetector;
use AgentDetector\KnownAgent;
use AgentDetector\Laravel\AgentContext;

it('reports agent detected when env var set', function () {
    putenv('CLAUDECODE=1');
    $ctx = new AgentContext(AgentDetector::detect());
    expect($ctx->isAgent())->toBeTrue();
});

it('reports no agent when env var absent', function () {
    $ctx = new AgentContext(AgentDetector::detect());
    expect($ctx->isAgent())->toBeFalse();
});

it('returns name when agent detected', function () {
    putenv('CLAUDECODE=1');
    $ctx = new AgentContext(AgentDetector::detect());
    expect($ctx->name())->toBe('claude');
});

it('returns null name when no agent', function () {
    $ctx = new AgentContext(AgentDetector::detect());
    expect($ctx->name())->toBeNull();
});

it('returns sessionId from CODEX_THREAD_ID', function () {
    putenv('CODEX_THREAD_ID=thread-abc123');
    $ctx = new AgentContext(AgentDetector::detect());
    expect($ctx->sessionId())->toBe('thread-abc123');
});

it('returns null sessionId for agents without session env var', function () {
    putenv('CLAUDECODE=1');
    $ctx = new AgentContext(AgentDetector::detect());
    expect($ctx->sessionId())->toBeNull();
});

it('returns correct KnownAgent enum', function () {
    putenv('CLAUDECODE=1');
    $ctx = new AgentContext(AgentDetector::detect());
    expect($ctx->knownAgent())->toBe(KnownAgent::Claude);
});

it('returns null knownAgent when no agent', function () {
    $ctx = new AgentContext(AgentDetector::detect());
    expect($ctx->knownAgent())->toBeNull();
});
