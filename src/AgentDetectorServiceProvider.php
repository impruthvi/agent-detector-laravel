<?php

namespace AgentDetector\Laravel;

use AgentDetector\AgentDetector;
use AgentDetector\Laravel\Logging\AgentLogFormatter;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

class AgentDetectorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AgentContext::class, fn () => new AgentContext(AgentDetector::detect()));
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/agent-detector.php' => config_path('agent-detector.php'),
        ]);

        if (config('agent-detector.disable_csrf', true)) {
            if ($this->app->bound(VerifyCsrfToken::class)) {
                $this->app->extend(VerifyCsrfToken::class, function ($original, $app) {
                    $ctx = $app->make(AgentContext::class);

                    return new class($original, $ctx)
                    {
                        public function __construct(
                            private VerifyCsrfToken $wrapped,
                            private AgentContext $ctx,
                        ) {}

                        public function handle($request, \Closure $next): mixed
                        {
                            return $this->ctx->isAgent()
                                ? $next($request)
                                : $this->wrapped->handle($request, $next);
                        }
                    };
                });
            } else {
                Log::warning(
                    'agent-detector: CSRF bypass unavailable via extend() '
                    .'(VerifyCsrfToken not container-bound). '
                    .'Use AgentAwareCsrfMiddleware in bootstrap/app.php instead.'
                );
            }
        }

        Log::extend('agent-detector', function ($app, array $config) {
            $ctx = $app->make(AgentContext::class);
            $displayName = $ctx->displayName();
            $stream = new StreamHandler(
                $config['path'] ?? storage_path('logs/agent.log'),
                Level::fromName($config['level'] ?? 'debug')
            );
            $stream->setFormatter(new AgentLogFormatter(
                agentName: $displayName,
                sessionId: $ctx->sessionId() ?? 'none',
            ));
            $logger = new Logger('agent');
            $logger->pushHandler($stream);

            return $logger;
        });

        if ($channel = config('agent-detector.log_channel')) {
            config(["logging.channels.{$channel}" => [
                'driver' => 'agent-detector',
                'path' => storage_path("logs/{$channel}.log"),
                'level' => 'debug',
            ]]);
        }
    }
}
