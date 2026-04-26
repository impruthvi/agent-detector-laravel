<?php

use AgentDetector\Laravel\AgentContext;
use AgentDetector\Laravel\Middleware\AgentAwareCsrfMiddleware;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;

function makeRequestWithSession(): Request
{
    $request = Request::create('/test', 'POST');
    $session = app('session')->driver('array');
    $session->start();
    $request->setLaravelSession($session);
    return $request;
}

it('bypasses CSRF for agent requests', function () {
    putenv('CLAUDECODE=1');
    app()->forgetInstance(AgentContext::class);

    $middleware = app(AgentAwareCsrfMiddleware::class);
    $response = $middleware->handle(makeRequestWithSession(), fn () => new Response('ok'));

    expect($response->getContent())->toBe('ok');
});

it('enforces CSRF for non-agent requests', function () {
    // Override runningUnitTests() so VerifyCsrfToken actually enforces the token check
    $middleware = new class(
        app(AgentContext::class),
        app(),
        app(Encrypter::class)
    ) extends AgentAwareCsrfMiddleware {
        protected function runningUnitTests(): bool
        {
            return false;
        }
    };

    expect(fn () => $middleware->handle(makeRequestWithSession(), fn () => new Response('ok')))
        ->toThrow(TokenMismatchException::class);
});

it('allows subclassing to add custom $except array', function () {
    $subclass = new class(
        app(AgentContext::class),
        app(),
        app(Encrypter::class)
    ) extends AgentAwareCsrfMiddleware {
        protected $except = ['/test'];
    };

    $response = $subclass->handle(makeRequestWithSession(), fn () => new Response('ok'));
    expect($response->getContent())->toBe('ok');
});
