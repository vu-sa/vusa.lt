# Gemini Instructions

Read [AGENTS.md](AGENTS.md) before making changes. It is the shared, current source of project conventions, stack versions, verification commands, and security rules.

Then read any path-specific `CLAUDE.md` files and the matching entries in [.ai/rules/index.md](.ai/rules/index.md) for the files you touch.

## Tools

- Read, search and list files with the built-in tools (`view_file`, `grep_search`, `list_dir`, `find_by_name`, `view_code_item`), not shell commands.
- Do not write Python (or other) scripts to explore or analyse the repository. For counts and aggregates, use `rg`, `rg -c`, `wc -l` or `git ls-files` in a pipeline.
- Run PHP and Node through `./vendor/bin/sail`.
