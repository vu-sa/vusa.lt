# Contributing

Thanks for helping. VU SR members with push access work on branches in this repository; everyone else forks it and
opens a pull request from the fork. Either way, pull requests go into `dev`, never `main`.

1. **Set up** the project with [README.md](README.md#set-up-locally).
2. **Learn how it fits together** in [`docs/kurejams/`](docs/kurejams/index.md) (in Lithuanian; English in
   [`docs/en/developers/`](docs/en/developers/index.md)). Start with *Pirmas pakeitimas* / *Your first change*, which
   walks through one change from branch to pull request.
3. **Follow the conventions** in [`AGENTS.md`](AGENTS.md). Before editing a file, open
   [`.ai/rules/index.md`](.ai/rules/index.md) and read the rules whose globs cover it. The same files guide AI coding
   agents, so a change that follows them reads the same whoever wrote it.
4. **Check your change** with `./dev/check-changed.sh`, then open the pull request. The template lists what "done"
   means.

Keep commits, branches and code in English. User-facing text needs both Lithuanian and English. A new feature should
be agreed in an issue or with the maintainers before you build it. Questions: <it@vusa.lt>.
