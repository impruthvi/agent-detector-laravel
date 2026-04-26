# agent-detector-laravel

Laravel companion for [shipfastlabs/agent-detector](https://github.com/shipfastlabs/agent-detector).

Install it, add two lines to `bootstrap/app.php`, and your Laravel app automatically:

- **Bypasses CSRF** for AI agent requests (no more 419s)
- **Logs every request** to a separate `agent.log` channel tagged with agent name + session ID
- **Adds `X-Agent-Session` header** to responses (visible in proxy/CDN logs)

```
[2026-04-23 14:32:01] agent.INFO: POST /api/users [] {"agent":"Claude Code","session":"none"}
[2026-04-23 14:32:03] agent.ERROR: Migration failed [] {"agent":"Codex","session":"thread-abc123"}
```

Grep by session ID. See exactly what your AI agent did.

---

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13
- [shipfastlabs/agent-detector](https://packagist.org/packages/shipfastlabs/agent-detector) `^1.1`

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

That's it. CSRF bypass and the agent log channel are active automatically.

---

## What you get

### CSRF bypass

Agent requests skip CSRF verification. No more 419s when Claude Code, Codex, or Cursor hits your endpoints.

The package tries two approaches, in order:

**Approach 1 (automatic):** Decorates `VerifyCsrfToken` via the service container. Works on most Laravel 11+ installs.

**Approach 2 (manual fallback):** If Approach 1 doesn't work, you'll see a warning in your logs:

```
agent-detector: CSRF bypass unavailable via extend() (VerifyCsrfToken not container-bound).
Use AgentAwareCsrfMiddleware in bootstrap/app.php instead.
```

Fix it by replacing the middleware in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->replace(
        \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        \AgentDetector\Laravel\Middleware\AgentAwareCsrfMiddleware::class,
    );
})
```

You can subclass `AgentAwareCsrfMiddleware` to add your own `$except` array:

```php
class MyMiddleware extends AgentAwareCsrfMiddleware
{
    protected $except = ['webhooks/*'];
}
```

### Agent log channel

Every log line emitted during an agent session is tagged with the agent name and session ID.

The `agent` channel is auto-registered. Use it directly:

```php
use Illuminate\Support\Facades\Log;

Log::channel('agent')->info('Migration started', ['table' => 'users']);
```

Logs write to `storage/logs/agent.log`.

### `X-Agent-Session` response header

Every response to a detected agent request includes:

```
X-Agent-Session: Claude Code/no-session
X-Agent-Session: Codex/thread-abc123
```

Useful for debugging via proxy logs, CDN access logs, or browser devtools.

---

## Configuration

`config/agent-detector.php` (after publishing):

```php
return [
    // Skip CSRF for detected agent requests. Default: true.
    'disable_csrf' => true,

    // Auto-registered log channel name. Set to null to disable.
    'log_channel' => 'agent',

    // Middleware must be added manually — no auto-injection.
    'auto_register_middleware' => false,
];
```

---

## Using AgentContext

Inject `AgentContext` anywhere in your app:

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

        // ...
    }
}
```

| Method | Returns | Example |
|--------|---------|---------|
| `isAgent()` | `bool` | `true` |
| `name()` | `?string` | `'claude'` |
| `displayName()` | `string` | `'Claude Code'` |
| `sessionId()` | `?string` | `'thread-abc123'` or `null` |
| `knownAgent()` | `?KnownAgent` | `KnownAgent::Claude` |

---

## Session IDs

Most agents do not expose a session ID. Only Codex (`CODEX_THREAD_ID`) and Amp (`AMP_CURRENT_THREAD_ID`) expose session env vars today. Log output shows `session:"none"` for all others — that's expected.

---

## Known limitations

**Octane / long-running processes:** `AgentContext` is resolved once when the application boots. Under Laravel Octane or long-running `queue:work`, detection reflects the environment at startup. Agents set env vars before the process starts, so this is rarely an issue in practice.

---

## Detected agents

Inherits detection from `shipfastlabs/agent-detector`:

| Agent | Display name | Session ID |
|-------|-------------|------------|
| Claude Code | `Claude Code` | — |
| Cursor | `Cursor` | — |
| Codex | `Codex` | `CODEX_THREAD_ID` |
| Amp | `Amp` | `AMP_CURRENT_THREAD_ID` |
| Devin | `Devin` | — |
| Gemini CLI | `Gemini CLI` | — |
| Augment CLI | `Augment CLI` | — |
| OpenCode | `OpenCode` | — |
| Replit | `Replit` | — |
| GitHub Copilot | `GitHub Copilot` | — |
| Antigravity | `Antigravity` | — |
| Pi | `Pi` | — |

---

## CI

| Laravel | PHP |
|---------|-----|
| 11, 12 | 8.2, 8.3, 8.4, 8.5 |
| 13 | 8.4, 8.5 |

---

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
