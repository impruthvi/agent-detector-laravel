<?php

namespace AgentDetector\Laravel\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\LogRecord;

class AgentLogFormatter extends LineFormatter
{
    public function __construct(
        private string $agentName,
        private string $sessionId,
    ) {
        parent::__construct(
            format: "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            allowInlineLineBreaks: true,
        );
    }

    public function format(LogRecord $record): string
    {
        $record = $record->with(extra: array_merge($record->extra, [
            'agent' => $this->agentName,
            'session' => $this->sessionId,
        ]));

        return parent::format($record);
    }
}
