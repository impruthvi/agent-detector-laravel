<?php

namespace AgentDetector\Laravel;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectedAgentMiddleware
{
    public function __construct(private AgentContext $ctx) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->ctx->isAgent()) {
            return $next($request);
        }

        $displayName = $this->ctx->displayName();
        $sessionId = $this->ctx->sessionId() ?? 'no-session';

        return $next($request)->header('X-Agent-Session', "{$displayName}/{$sessionId}");
    }
}
