<?php

namespace AgentDetector\Laravel\Middleware;

use AgentDetector\Laravel\AgentContext;
use Closure;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

class AgentAwareCsrfMiddleware extends VerifyCsrfToken
{
    public function __construct(
        private AgentContext $ctx,
        Application $app,
        Encrypter $encrypter,
    ) {
        parent::__construct($app, $encrypter);
    }

    public function handle($request, Closure $next): mixed
    {
        if ($this->ctx->isAgent()) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
