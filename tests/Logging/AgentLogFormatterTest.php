<?php

use AgentDetector\Laravel\Logging\AgentLogFormatter;
use Monolog\Level;
use Monolog\LogRecord;

function makeRecord(string $message = 'test message'): LogRecord
{
    return new LogRecord(
        datetime: new DateTimeImmutable(),
        channel: 'agent',
        level: Level::Info,
        message: $message,
        context: [],
        extra: [],
    );
}

it('includes agent name in formatted output', function () {
    $formatter = new AgentLogFormatter(agentName: 'Claude Code', sessionId: 'sess-123');
    $output    = $formatter->format(makeRecord());
    expect($output)->toContain('Claude Code');
});

it('includes session id in formatted output', function () {
    $formatter = new AgentLogFormatter(agentName: 'Claude Code', sessionId: 'sess-abc123');
    $output    = $formatter->format(makeRecord());
    expect($output)->toContain('sess-abc123');
});

it('does not drop %extra% from format string', function () {
    $formatter = new AgentLogFormatter(agentName: 'Codex', sessionId: 'thread-xyz');
    $output    = $formatter->format(makeRecord());
    // Both extra fields must appear — format string includes %extra%
    expect($output)->toContain('Codex')->toContain('thread-xyz');
});

it('outputs valid lines for non-agent context', function () {
    $formatter = new AgentLogFormatter(agentName: 'unknown', sessionId: 'none');
    $output    = $formatter->format(makeRecord());
    expect($output)->toContain('unknown')->toContain('none');
});
