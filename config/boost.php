<?php

declare(strict_types=1);

/**
 * Only the keys this project overrides; the rest come from the package config.
 * `boost:update` rewrites AGENTS.md from these, so CI fails on a stale copy.
 */
return [
    'rules' => [
        'enabled' => env('BOOST_RULES_ENABLED', true),
        // Path-scoped Boost guidelines go to .ai/rules/boost/ instead of AGENTS.md, keeping it under Codex's 32 KiB limit.
        'scoped_guidelines' => env('BOOST_RULES_SCOPED_GUIDELINES', true),
    ],

    'agents' => [
        // CLAUDE.md only imports AGENTS.md; without this Boost appends a second copy of its block to CLAUDE.md.
        'claude_code' => ['guidelines_path' => 'AGENTS.md'],
    ],

    'guidelines' => [
        // Recommends Laravel Cloud; production runs on a VPS (see .ai/rules/deploy.md).
        'exclude' => ['deployments'],
    ],
];
