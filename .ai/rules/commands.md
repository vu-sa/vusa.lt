---
paths:
  - 'app/Console/Commands/**'
---

# Commands

## Commands show progress by default
A command that loops over records, files or jobs shows progress without any flag: a progress bar per step (`$this->output->createProgressBar()`) and a closing summary of what it did. `-v` adds per-item detail; `--quiet` silences. A command that queues work prints what it queued and follows the batch's progress unless told to `--detach`, so the operator is never left at a silent prompt.
