---
paths:
  - CLAUDE.md
---

# General

## Don't append Boost guidelines to CLAUDE.md — it double-loads them
CLAUDE.md contains only `@AGENTS.md` (no other content). AGENTS.md already carries a `<laravel-boost-guidelines>` block appended by `boost:install` for other agents (Codex etc.). If `boost:install` re-adds a guidelines block directly into CLAUDE.md, it duplicates ~5.6K tokens on every session and every subagent spawn, since CLAUDE.md's `@AGENTS.md` import already pulls in AGENTS.md's copy. Strip any block re-added to CLAUDE.md after running the installer.
