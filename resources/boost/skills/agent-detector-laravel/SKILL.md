---
name: agent-detector-laravel
description: Detect AI coding agents (Claude Code, Cursor, Codex, Copilot, etc.) in a Laravel application. Covers CSRF bypass, agent-tagged logging, X-Agent-Session response headers, and injecting AgentContext anywhere in the app.
---

# Agent Detector for Laravel

## When to use this skill

Use this skill when the user needs to detect AI coding agents inside a Laravel app using `impruthvi/agent-detector-laravel`. This includes bypassing CSRF for agent requests, writing agent-tagged logs, adding `X-Agent-Session` response headers, injecting `AgentContext` into controllers or services, and configuring which features are active.

## Installation

```bash
composer require impruthvi/agent-detector-laravel
```

Publish the config (optional):

```bash
php artisan vendor:publish --provider="AgentDetector\Laravel\AgentDetectorServiceProvider"
```

## Setup

Add the middleware to `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->prepend(\AgentDetector\Laravel\DetectedAgentMiddleware::class);
})
```

That's all. CSRF bypass and the agent log channel activate automatically.

## AgentContext

Inject `AgentContext` anywhere via the service container:

```php
use AgentDetector\Laravel\AgentContext;

class MyController extends Controller
{
    public function __construct(private AgentContext $agent) {}

    public function store(Request $request)
    {
        if ($this->agent->isAgent()) {
            Log::channel('agent')->info('Agent creating resource', [
                'agent'   => $this->agent->displayName(),
                'session' => $this->agent->sessionId(),
            ]);
        }
    }
}
```

### Available methods

| Method | Return type | Example |
|--------|-------------|---------|
| `isAgent()` | `bool` | `true` |
| `name()` | `?string` | `'claude'` |
| `displayName()` | `string` | `'Claude Code'` |
| `sessionId()` | `?string` | `'thread-abc123'` or `null` |
| `knownAgent()` | `?KnownAgent` | `KnownAgent::Claude` |

## CSRF bypass

Agent requests automatically skip CSRF verification — no more 419 errors when Claude Code, Codex, or Cursor hits your endpoints.

The package tries two approaches in order:

**Approach 1 (automatic):** Decorates `VerifyCsrfToken` via the service container. Works on most Laravel 11+ installs with no extra config.

**Approach 2 (manual fallback):** If you see this warning in your logs:

```
agent-detector: CSRF bypass unavailable via extend() (VerifyCsrfToken not container-bound).
Use AgentAwareCsrfMiddleware in bootstrap/app.php instead.
```

Replace the middleware manually in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->replace(
        \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        \AgentDetector\Laravel\Middleware\AgentAwareCsrfMiddleware::class,
    );
})
```

To add custom `$except` routes, subclass it:

```php
class MyMiddleware extends AgentAwareCsrfMiddleware
{
    protected $except = ['webhooks/*'];
}
```

Disable CSRF bypass entirely via config:

```php
// config/agent-detector.php
'disable_csrf' => false,
```

## Agent log channel

Every log line during an agent session is tagged with the agent name and session ID.

The `agent` channel is auto-registered. Use it directly:

```php
use Illuminate\Support\Facades\Log;

Log::channel('agent')->info('Migration started', ['table' => 'users']);
```

Log output format:

```
[2026-04-26 14:32:01] agent.INFO: Migration started [] {"agent":"Claude Code","session":"none"}
[2026-04-26 14:32:03] agent.ERROR: Migration failed [] {"agent":"Codex","session":"thread-abc123"}
```

Logs write to `storage/logs/agent.log` by default.

Change the channel name or disable it via config:

```php
// config/agent-detector.php
'log_channel' => 'agent',   // set to null to disable
```

## X-Agent-Session response header

Every response to a detected agent request includes:

```
X-Agent-Session: Claude Code/no-session
X-Agent-Session: Codex/thread-abc123
```

Requires `DetectedAgentMiddleware` to be registered (see Setup).

## Configuration

Full `config/agent-detector.php` reference:

```php
return [
    // Skip CSRF verification for detected agent requests.
    'disable_csrf' => true,

    // Name of the auto-registered log channel. Set null to disable.
    'log_channel' => 'agent',
];
```

## Detected agents

| Agent | `name()` | `displayName()` | Session env var |
|-------|----------|-----------------|-----------------|
| Claude Code | `claude` | `Claude Code` | `CLAUDE_CODE_SESSION_ID` |
| Cursor | `cursor` | `Cursor` | — |
| Codex | `codex` | `Codex` | `CODEX_THREAD_ID` |
| Amp | `amp` | `Amp` | `AMP_CURRENT_THREAD_ID` |
| Devin | `devin` | `Devin` | — |
| Gemini CLI | `gemini` | `Gemini CLI` | — |
| Augment CLI | `augment-cli` | `Augment CLI` | — |
| OpenCode | `opencode` | `OpenCode` | — |
| Replit | `replit` | `Replit` | — |
| GitHub Copilot | `copilot` | `GitHub Copilot` | — |
| Antigravity | `antigravity` | `Antigravity` | — |
| Pi | `pi` | `Pi` | — |

## Session IDs

Most agents do not expose a session ID. Only Codex (`CODEX_THREAD_ID`) and Amp (`AMP_CURRENT_THREAD_ID`) set session env vars. `sessionId()` returns `null` for all others — that is expected behaviour.

## Known limitations

**Octane / long-running processes:** `AgentContext` is resolved once at application boot. Detection reflects the environment at startup. Agents set env vars before the process starts, so this is rarely an issue in practice.
