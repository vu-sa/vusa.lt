# Antigravity notes

Antigravity loads this file next to `AGENTS.md`; Copilot reads it too, so these notes name their tool. Project conventions live in `AGENTS.md` and the path-scoped rules in `.ai/rules/index.md`.

- Antigravity: read, search and list files with the built-in tools (`view_file`, `grep_search`, `list_dir`, `find_by_name`, `view_code_item`), not shell commands.
- Do not write Python (or other) scripts to explore or analyse the repository. For counts and aggregates, use `rg`, `rg -c`, `wc -l` or `git ls-files` in a pipeline.
- Run PHP and Node through `./vendor/bin/sail`.
