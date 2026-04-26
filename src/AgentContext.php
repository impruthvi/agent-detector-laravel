<?php

namespace AgentDetector\Laravel;

use AgentDetector\AgentResult;
use AgentDetector\KnownAgent;

class AgentContext
{
    public function __construct(private AgentResult $result) {}

    public function isAgent(): bool
    {
        return $this->result->isAgent;
    }

    public function name(): ?string
    {
        return $this->result->name;
    }

    public function knownAgent(): ?KnownAgent
    {
        return $this->result->knownAgent();
    }

    public function sessionId(): ?string
    {
        return match ($this->result->name) {
            'codex' => getenv('CODEX_THREAD_ID') ?: null,
            'amp' => getenv('AMP_CURRENT_THREAD_ID') ?: null,
            'claude' => getenv('CLAUDE_CODE_SESSION_ID') ?: null,
            default => null,
        };
    }

    public function displayName(): string
    {
        return match ($this->result->name) {
            'claude' => 'Claude Code',
            'cursor' => 'Cursor',
            'devin' => 'Devin',
            'replit' => 'Replit',
            'gemini' => 'Gemini CLI',
            'codex' => 'Codex',
            'augment-cli' => 'Augment CLI',
            'opencode' => 'OpenCode',
            'amp' => 'Amp',
            'copilot' => 'GitHub Copilot',
            'antigravity' => 'Antigravity',
            'pi' => 'Pi',
            default => $this->result->name ?? 'unknown',
        };
    }
}
